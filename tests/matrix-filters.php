<?php
require_once __DIR__ . '/../lib/Modules/Reports/Service/MatrixReportService.php';
use OCA\Done\Modules\Reports\Service\MatrixReportService as Service;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
check(Service::selection(null) === null, 'Omitted filters are unrestricted');
check(Service::selection('') === [], 'Empty CSV is an explicit empty selection');
check(Service::selection([]) === [], 'Empty array is an explicit empty selection');
check(Service::selection('a,b,a') === ['a','b'], 'CSV selections deduplicate');
check(Service::selection(['a','b']) === ['a','b'], 'JSON selections use the same IDs');
foreach ([false, 3, ['id'=>'a'], [['a']], ['bad id'], 'a,,b', 'a;DROP', str_repeat('x',65),
          array_fill(0,1001,'a')] as $bad) {
    try { Service::selection($bad); throw new RuntimeException('Accepted invalid filter'); }
    catch (InvalidArgumentException $e) {}
}
echo "Filter validation, CSV/JSON parity and explicit none/all: passed\n";
