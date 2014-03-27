<pre>

<?php
$subject="hllo {hyarticle 2;title;fullimg} knjnjnj prjrsjd {hyarticle 6;introimg} kmkmkojj njnjnj {hyarticle 7}";
$pattern = '/{hyarticle (?P<id>\d+)(?P<title>;title)?(?P<introimg>;introimg)?(?P<fullimg>;fullimg)?}/';

for ($i=0,$offset=0;;$i++) {
	if (!preg_match ($pattern, $subject, $matches[$i], PREG_OFFSET_CAPTURE, $offset)) break;
	else $offset=$matches[$i][0][1]+1;	
}

echo $subject."<br/><br/>";
print_r($matches);

echo $matches[2][fullimg][0] ? "yes" : "no";
echo "<br/>";
echo $matches[0][introimg][0] ? "yes" : "no";
echo "<br/>";
echo $matches[0][fullimg][0] ? "yes" : "no";
?>
</pre>
