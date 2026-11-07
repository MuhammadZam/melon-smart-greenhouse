"""Validate and render exports from revision_browser.mjs (run at repository root)."""
from pathlib import Path
import csv
import json
import fitz

root = Path('docs/revision/evidence')
pdf = fitz.open(root / 'monitoring.pdf')
text = '\n'.join(page.get_text() for page in pdf)
for number, page in enumerate(pdf, 1):
    page.get_pixmap(matrix=fitz.Matrix(1.3, 1.3)).save(root / f'pdf-page-{number}.png')
(root / 'pdf-text.txt').write_text(text, encoding='utf-8')
rows = list(csv.reader((root / 'monitoring.csv').read_text(encoding='utf-8-sig').splitlines()))
retired = ['Kelembapan Udara', 'Konduktivitas', 'Intensitas Cahaya', 'Suhu Nutrisi', 'Level Tandon', 'FAN-01', '8 PARAMETER', '16 aturan']
result = {
    'pdf_pages': len(pdf),
    'pdf_retired_labels': [label for label in retired if label.lower() in text.lower()],
    'csv_retired_headers': [label for label in retired if any(label.lower() in heading.lower() for heading in rows[0])],
    'csv_headers': rows[0],
    'csv_rows': len(rows) - 1,
    'csv_column_counts': sorted(set(map(len, rows))),
    'pdf_has_water_ph': 'pH air' in text,
    'pdf_has_provisional': 'PROVISIONAL' in text,
    'pdf_correct_category_boundaries': 'skor < 65' in text and 'skor < 40' in text,
    'csv_has_water_ph': 'pH air' in rows[0],
}
result['passed'] = (
    not result['pdf_retired_labels'] and not result['csv_retired_headers']
    and len(result['csv_column_counts']) == 1
    and all(result[key] for key in ['pdf_has_water_ph', 'pdf_has_provisional', 'pdf_correct_category_boundaries', 'csv_has_water_ph'])
)
(root / 'export-validation.json').write_text(json.dumps(result, indent=2), encoding='utf-8')
print(json.dumps(result, indent=2))
if not result['passed']:
    raise SystemExit(1)
