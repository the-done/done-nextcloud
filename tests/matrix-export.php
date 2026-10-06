<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/Modules/Reports/Service/MatrixReportBuilder.php';
require_once __DIR__ . '/../lib/Modules/Reports/Service/MatrixReportExport.php';
use OCA\Done\Modules\Reports\Service\MatrixReportBuilder as Builder;
use OCA\Done\Modules\Reports\Service\MatrixReportExport as Export;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$calendar=Builder::period('2026-09-01','month',false);
$name='=HYPERLINK("https://example.test", "formula")';
$matrix=Builder::build($calendar,'employees',[
 ['date'=>'2026-09-01','user_id'=>'a','user_name'=>$name,'project_id'=>'p','project_name'=>'<script>alert(1)</script> Проект','minutes'=>61],
 ['date'=>'2026-09-02','user_id'=>'b','user_name'=>'Анна Петрова','project_id'=>'p','project_name'=>'<script>alert(1)</script> Проект','minutes'=>62],
]);
$expanded=array_column($matrix['rows'],'id');
$html=base64_decode(Export::create($matrix,$expanded,'month','html')['content']);
check(!str_contains($html,'<script>'),'HTML names must be escaped');
check(str_contains($html,'&lt;script&gt;'),'Escaped project included');
check(str_contains($html,'Анна Петрова'),'Unicode preserved');
$file=tempnam(sys_get_temp_dir(),'done-matrix-test-');
try {
 file_put_contents($file,base64_decode(Export::create($matrix,$expanded,'month','xlsx')['content']));
 $book=\PhpOffice\PhpSpreadsheet\IOFactory::load($file);
 $sheet=$book->getActiveSheet();
 check($sheet->getHighestDataRow()===9,'Visible Excel hierarchy');
 check($sheet->getCell('A6')->getDataType()==='s','Formula-like name must remain text');
 check($sheet->getCell('A6')->getValue()===$name,'Formula-like name unchanged');
 check(abs($sheet->getCell('B5')->getValue()-123/60)<0.0000001,'Exact numeric total, not rounded text');
 check(abs($sheet->getCell('G6')->getValue()-61/60)<0.0000001,'Exact employee total');
} finally { unlink($file); }
$pdf=base64_decode(Export::create($matrix,$expanded,'month','pdf')['content']);
check(str_starts_with($pdf,'%PDF-'),'Real PDF output');
echo "Export hierarchy, numeric hours, HTML escaping, formula injection and Unicode: passed\n";
