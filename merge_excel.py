#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script de fusion Excel - Approche ZIP pure
Les fichiers Excel sont des ZIP, on les manipule directement pour préserver TOUT
y compris les images, charts, etc.
"""

import sys
import os
from zipfile import ZipFile, ZIP_DEFLATED
import tempfile
import shutil
import xml.etree.ElementTree as ET

NS = {
    'rel': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'sheet': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
    'rels': 'http://schemas.openxmlformats.org/package/2006/relationships'
}

def merge_excel_zip(file_paths, output_path):
    """
    Stratégie ZIP-native: 
    1. Copier le premier fichier intact
    2. Extraire et ajouter les feuilles des autres fichiers au ZIP
    """
    
    print(f"Fusion {len(file_paths)} fichier(s) via ZIP...", file=sys.stderr)
    
    if len(file_paths) == 1:
        print(f"  Un seul fichier, copie simple", file=sys.stderr)
        shutil.copy2(file_paths[0], output_path)
        return
    
    temp_dir = tempfile.mkdtemp()
    
    try:
        # Étape 1: Extraire le premier fichier (base)
        base_dir = os.path.join(temp_dir, 'base')
        print(f"  Base: {os.path.basename(file_paths[0])}", file=sys.stderr)
        
        with ZipFile(file_paths[0], 'r') as zf:
            zf.extractall(base_dir)
        
        # Charger le workbook.xml du premier fichier
        workbook_xml_path = os.path.join(base_dir, 'xl', 'workbook.xml')
        workbook_tree = ET.parse(workbook_xml_path)
        workbook_root = workbook_tree.getroot()
        
        # Charger les relationships
        workbook_rels_path = os.path.join(base_dir, 'xl', '_rels', 'workbook.xml.rels')
        rels_tree = ET.parse(workbook_rels_path)
        rels_root = rels_tree.getroot()
        
        # Traiter les autres fichiers
        sheet_count = len(workbook_root.findall('.//sheet:sheet', NS)) + 1
        rel_id = max([int(r.get('Id').replace('rId', '')) for r in rels_root.findall('rel:Relationship', NS)]) + 1
        
        for i, file_path in enumerate(file_paths[1:], 1):
            print(f"  Fichier {i+1}/{len(file_paths)}: {os.path.basename(file_path)}", file=sys.stderr)
            
            source_dir = os.path.join(temp_dir, f'source_{i}')
            
            # Extraire le fichier source
            with ZipFile(file_path, 'r') as zf:
                zf.extractall(source_dir)
            
            # Charger le workbook du source
            source_workbook_xml = os.path.join(source_dir, 'xl', 'workbook.xml')
            source_tree = ET.parse(source_workbook_xml)
            source_root = source_tree.getroot()
            
            # Copier les feuilles du source
            source_sheets = source_root.findall('.//sheet:sheet', NS)
            
            for sheet in source_sheets:
                sheet_name = sheet.get('name')
                sheet_id = sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
                
                print(f"    Ajout: {sheet_name}", file=sys.stderr)
                
                # Copier le fichier de la feuille
                sheet_rId = sheet_id.replace('rId', '')
                source_rels = ET.parse(os.path.join(source_dir, 'xl', '_rels', 'workbook.xml.rels')).getroot()
                sheet_source = source_rels.find(f'.//rel:Relationship[@Id="{sheet_id}"]', NS)
                
                if sheet_source is not None:
                    source_sheet_path = sheet_source.get('Target')
                    source_sheet_file = os.path.join(source_dir, 'xl', source_sheet_path)
                    
                    # Copier la feuille dans la base
                    dest_sheet_file = os.path.join(base_dir, 'xl', os.path.basename(source_sheet_path).replace('.xml', f'_{sheet_count}.xml'))
                    shutil.copy2(source_sheet_file, dest_sheet_file)
                    
                    # Ajouter la relation
                    new_rel = ET.Element('{http://schemas.openxmlformats.org/package/2006/relationships}Relationship')
                    new_rel.set('Id', f'rId{rel_id}')
                    new_rel.set('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet')
                    new_rel.set('Target', f'worksheets/{os.path.basename(dest_sheet_file)}')
                    rels_root.append(new_rel)
                    
                    # Ajouter la feuille au workbook.xml
                    sheets_elem = workbook_root.find('.//sheet:sheets', NS)
                    new_sheet = ET.Element('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet')
                    new_sheet.set('name', sheet_name)
                    new_sheet.set('sheetId', str(sheet_count))
                    new_sheet.set('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id', f'rId{rel_id}')
                    sheets_elem.append(new_sheet)
                    
                    sheet_count += 1
                    rel_id += 1
        
        # Sauvegarder les modifications
        workbook_tree.write(workbook_xml_path, encoding='UTF-8', xml_declaration=True)
        rels_tree.write(workbook_rels_path, encoding='UTF-8', xml_declaration=True)
        
        # Créer le ZIP final
        print(f"  Sauvegarde: {output_path}", file=sys.stderr)
        
        with ZipFile(output_path, 'w', ZIP_DEFLATED) as zf_out:
            for root, dirs, files in os.walk(base_dir):
                for file in files:
                    file_path = os.path.join(root, file)
                    arcname = os.path.relpath(file_path, base_dir)
                    zf_out.write(file_path, arcname)
        
        print(f"Fusion complétée: {output_path}", file=sys.stderr)
    
    finally:
        shutil.rmtree(temp_dir, ignore_errors=True)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python merge_excel.py <output> <file1> [file2] ...", file=sys.stderr)
        sys.exit(1)
    
    try:
        merge_excel_zip(sys.argv[2:], sys.argv[1])
        print("SUCCESS")
        sys.exit(0)
    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)
        import traceback
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)






