<?php
// Read-only source extraction. No application or database mutation.
$root = dirname(__DIR__, 3);
$files = [
    'SRS_Web_Monitoring_Greenhouse_Melon.docx',
    'SPESIFIKASI_AKHIR_SISTEM_MONITORING_DAN_KONTROL_MELON.docx',
    'docs/srs/SRS_Sistem_Web_Monitoring_Tanaman_Melon.docx',
];
foreach ($files as $file) {
    $zip = new ZipArchive();
    if ($zip->open($root.'/'.$file) !== true) { echo "MISSING $file\n"; continue; }
    $xml = simplexml_load_string($zip->getFromName('word/document.xml'));
    $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $out = [];
    foreach ($xml->xpath('//w:p') as $i => $p) {
        $p->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $text = implode('', array_map('strval', $p->xpath('.//w:t')));
        if (trim($text) !== '') $out[] = 'P'.($i+1).': '.$text;
    }
    file_put_contents(__DIR__.'/'.basename($file, '.docx').'.txt', implode("\n", $out));
    echo $file.' => '.count($out)." paragraphs\n";
    $zip->close();
}
$zip = new ZipArchive();
$zip->open($root.'/docs/srs/SRS_Requirement_Traceability_Matrix.xlsx');
$strings=[];
if ($shared = $zip->getFromName('xl/sharedStrings.xml')) {
    $sx=simplexml_load_string($shared);
    foreach($sx->si as $si) $strings[]=implode('', $si->xpath('.//*[local-name()="t"]'));
}
$out=[];
for($i=0;$i<$zip->numFiles;$i++) {
    $name=$zip->getNameIndex($i);
    if(!preg_match('~xl/worksheets/sheet\d+\.xml$~',$name)) continue;
    $sx=simplexml_load_string($zip->getFromName($name));
    $out[]=$name;
    foreach($sx->sheetData->row as $row) {
        $cells=[];
        foreach($row->c as $c) {
            $type=(string)$c['t'];
            $cells[]=$type==='s' ? ($strings[(int)$c->v]??'') : ($type==='inlineStr' ? implode('', $c->xpath('.//*[local-name()="t"]')) : (string)$c->v);
        }
        $out[]=implode(' | ',$cells);
    }
}
file_put_contents(__DIR__.'/existing_rtm.txt',implode("\n",$out));
echo 'RTM extracted'.PHP_EOL;
