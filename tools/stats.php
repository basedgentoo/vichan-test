#!/usr/bin/php
<?php
require __DIR__ . '/inc/cli.php';
                
$variants = [["hour", 3600], ["day", 3600*24], ["3 days", 3600*24*3], ["week", 3600*24*7], ["month", 3600*24*7*30]];

printf("           || ");
foreach ($variants as $iter) {
	[$term, $time] = $iter;
	printf("%8s | ", $term);
}
print("\n");
print(str_repeat('=', 13+11*count($variants)));
print("\n");


$q = query("SELECT uri FROM ``boards``");
while ($f = $q->fetch()) {
	printf("%10s || ", $f['uri']);
	foreach ($variants as $iter) {
		[$term, $time] = $iter;
		$qq = query(sprintf("SELECT COUNT(*) as count FROM ``posts_%s`` WHERE time > %d", $f['uri'], time()-$time));
		$c = $qq->fetch()['count'];

		printf("%8d | ", $c);
	}
	print("\n");
}
