<?php
require_once __DIR__ . '/../lib/Modules/Reports/Service/MatrixReportBuilder.php';
use OCA\Done\Modules\Reports\Service\MatrixReportBuilder as Builder;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$month = Builder::period('2026-10-06', 'month', false);
check($month['columns'][0] === ['start'=>'2026-10-01','end'=>'2026-10-04'], 'Partial first week');
check(end($month['columns']) === ['start'=>'2026-10-26','end'=>'2026-10-31'], 'Partial final week');
check(count(Builder::period('2024-02-20','month',true)['columns'])===29, 'Leap year');
$week=Builder::period('2026-01-01','week',false);
check($week['start']==='2025-12-29' && $week['end']==='2026-01-04','Cross-year ISO week');
check(Builder::period('2026-10-04','week',false)['start']==='2026-09-28','Sunday belongs to previous Monday');
check(count(Builder::period('2026-06-30','year',false)['columns'])===12,'Twelve months');
check(Builder::period('2026-06-30','year',false)['end']==='2026-12-31','Year end');
try { Builder::date('2026-02-30'); check(false,'Invalid date accepted'); } catch (InvalidArgumentException $e) {}
$data=[
 ['date'=>'2026-10-01','user_id'=>'a','user_name'=>'Alice','project_id'=>'p','project_name'=>'Portal','minutes'=>61],
 ['date'=>'2026-10-02','user_id'=>'a','user_name'=>'Alice','project_id'=>'q','project_name'=>'Shop','minutes'=>62],
 ['date'=>'2026-10-06','user_id'=>'b','user_name'=>'Bob','project_id'=>'p','project_name'=>'Portal','minutes'=>63],
 ['date'=>'2026-09-30','user_id'=>'b','user_name'=>'Bob','project_id'=>'p','project_name'=>'Portal','minutes'=>1000],
];
foreach(['employees','projects'] as $mode) {
 $result=Builder::build($month,$mode,$data);
 check($result['company']['total']===186,'Exact minutes, no leakage');
 check($result['company']['cells']===[123,63,0,0,0],'Correct buckets');
 foreach($result['rows'] as $row) {
  check(array_sum(array_column($row['children'],'total'))===$row['total'],'Children sum to parent');
  check(array_sum($row['cells'])===$row['total'],'Cells sum to row');
 }
 check(count(Builder::visibleRows($result,[]))===3,'Collapsed export');
 check(count(Builder::visibleRows($result,array_column($result['rows'],'id')))===6,'Expanded export');
}
check(Builder::build($month,'employees',[])['company']['total']===0,'Empty period');
$linked = $data[0] + ['user_slug'=>'alice', 'project_slug'=>'p'];
foreach (['employees'=>'user', 'projects'=>'project'] as $mode=>$parentType) {
 $linkedMatrix = Builder::build($month, $mode, [$linked]);
 $parent = $linkedMatrix['rows'][0];
 check($parent[$parentType.'_slug'] === $linked[$parentType.'_slug'], 'Parent profile route');
 check($parent['children'][0]['user_slug'] === 'alice', 'Child employee profile route');
 check($parent['children'][0]['project_slug'] === 'p', 'Child project card route');
}
check(Builder::build($month,'employees',$data)['rows'][0]['children'][0]['project_slug'] === null,
 'Missing profile remains unlinked');
$matrix = Builder::build($month, 'employees', $data);
$reverse = Builder::sorted($matrix, 'name', 'desc');
check(array_column($reverse['rows'], 'name') === ['Bob', 'Alice'], 'Reverse alphabetical order');
$hours = Builder::sorted($matrix, 'column:1', 'desc');
check(array_column($hours['rows'], 'name') === ['Bob', 'Alice'], 'Sort selected column, including zero hours');
$total = Builder::sorted($matrix, 'total', 'desc');
check(array_column($total['rows'], 'name') === ['Alice', 'Bob'], 'Sort period totals');
check(array_column($total['rows'][0]['children'], 'name') === ['Shop', 'Portal'], 'Sort children inside parent');
$ties = Builder::sorted($matrix, 'column:4', 'desc');
check(array_column($ties['rows'], 'name') === ['Alice', 'Bob'], 'Equal values have deterministic name order');
$export = Builder::visibleRows($reverse, array_column($reverse['rows'], 'id'));
check($export[0]['id'] === 'company' && $export[1]['name'] === 'Bob', 'Export preserves sort and company total');
check($hours['company'] === $matrix['company'], 'Sorting never changes totals');
$surnameData = [
 ['date'=>'2026-10-01','user_id'=>'a','user_name'=>'Alice Williams','user_sort_name'=>'Williams Alice',
  'project_id'=>'p','project_name'=>'Portal','minutes'=>60],
 ['date'=>'2026-10-01','user_id'=>'b','user_name'=>'Zoe Adams','user_sort_name'=>'Adams Zoe',
  'project_id'=>'p','project_name'=>'Portal','minutes'=>60],
 ['date'=>'2026-10-01','user_id'=>'c','user_name'=>'Ben Adams','user_sort_name'=>'Adams Ben',
  'project_id'=>'p','project_name'=>'Portal','minutes'=>60],
];
foreach (['employees','projects'] as $mode) {
 $bySurname = Builder::build($month, $mode, $surnameData);
 foreach (['asc'=>['Ben Adams','Zoe Adams','Alice Williams'],
           'desc'=>['Alice Williams','Zoe Adams','Ben Adams']] as $direction=>$names) {
  $sorted = Builder::sorted($bySurname, 'name', $direction);
  $siblings = $mode === 'employees' ? $sorted['rows'] : $sorted['rows'][0]['children'];
  check(array_column($siblings,'name') === $names, 'Surname sorting in both hierarchies and directions');
 }
}
foreach ([['column:99','asc'], ['bad','asc'], ['name','bad']] as [$key,$direction]) {
 try { Builder::sorted($matrix,$key,$direction); check(false,'Invalid sorting accepted'); }
 catch (InvalidArgumentException $e) {}
}
echo "Sorting by names, cells and totals, hierarchy and sorted export: passed\n";
echo "Calendar, exact aggregation, hierarchy, empty periods and export visibility: passed\n";
