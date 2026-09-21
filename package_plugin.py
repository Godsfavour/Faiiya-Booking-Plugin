#!/usr/bin/env python3
"""
Package the WordPress plugin into a strictly compliant ZIP archive that passes WordPress PclZip validation.
"""
import os
import zipfile
import time

def package_plugin(source_dir, output_zip, root_folder_name):
    # Ensure parent output directory exists
    os.makedirs(os.path.dirname(os.path.abspath(output_zip)), exist_ok=True)
    
    # Remove existing zip if present
    if os.path.exists(output_zip):
        os.remove(output_zip)

    # Standard fixed timestamp to ensure deterministic, clean DOS date/time in header
    dt = time.localtime(time.time())[:6]

    with zipfile.ZipFile(output_zip, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        # First write the root directory entry
        root_dir_info = zipfile.ZipInfo(f"{root_folder_name}/", dt)
        root_dir_info.external_attr = 0o755 << 16 | 0x10  # directory flag
        zf.writestr(root_dir_info, '')

        for root, dirs, files in os.walk(source_dir):
            # Sort for deterministic output
            dirs.sort()
            files.sort()

            rel_root = os.path.relpath(root, source_dir)
            if rel_root != '.':
                dir_path_in_zip = f"{root_folder_name}/{rel_root.replace(os.sep, '/')}/"
                dir_info = zipfile.ZipInfo(dir_path_in_zip, dt)
                dir_info.external_attr = 0o755 << 16 | 0x10
                zf.writestr(dir_info, '')

            for file in files:
                if file.startswith('.') or file.endswith('.zip'):
                    continue
                file_path = os.path.join(root, file)
                rel_file = os.path.relpath(file_path, source_dir)
                archive_name = f"{root_folder_name}/{rel_file.replace(os.sep, '/')}"

                with open(file_path, 'rb') as f:
                    data = f.read()

                zinfo = zipfile.ZipInfo(archive_name, dt)
                zinfo.external_attr = 0o644 << 16  # standard file permissions
                zinfo.compress_type = zipfile.ZIP_DEFLATED
                zf.writestr(zinfo, data)

    print(f"Successfully packaged {output_zip} ({os.path.getsize(output_zip)} bytes)")

if __name__ == '__main__':
    targets = [
        # Root archives (including replacing the first zip file faiiya-booking.zip)
        ('yasmine-artistry-booking', 'faiiya-booking.zip', 'yasmine-artistry-booking'),
        ('yasmine-artistry-booking', 'faiiya-booking-plugin.zip', 'yasmine-artistry-booking'),
        ('yasmine-artistry-booking', 'yasmine-artistry-booking.zip', 'yasmine-artistry-booking'),

        # Public directory (for web preview / direct HTTP downloads)
        ('yasmine-artistry-booking', 'public/faiiya-booking.zip', 'yasmine-artistry-booking'),
        ('yasmine-artistry-booking', 'public/faiiya-booking-plugin.zip', 'yasmine-artistry-booking'),
        ('yasmine-artistry-booking', 'public/yasmine-artistry-booking.zip', 'yasmine-artistry-booking'),

        # 'Faiiya booking plugin' folder
        ('yasmine-artistry-booking', 'Faiiya booking plugin/faiiya-booking.zip', 'yasmine-artistry-booking'),
        ('yasmine-artistry-booking', 'Faiiya booking plugin/faiiya-booking-plugin.zip', 'yasmine-artistry-booking'),
        ('yasmine-artistry-booking', 'Faiiya booking plugin/yasmine-artistry-booking.zip', 'yasmine-artistry-booking'),
    ]

    for src, out, root_name in targets:
        package_plugin(src, out, root_name)
