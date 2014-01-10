<?php
/*

Run against a text file extracted from an OnGuard PDF report using Xpdf's pdftotext.

pdftotext -enc UTF-8 -layout input.pdf output.txt
php extract output.txt

*/

mb_internal_encoding('UTF-8');

$input_path = $argv[1];

$input_handle = fopen($input_path, 'r');
if ($input_handle) {
	process($input_handle);
} else {
	stderr('Error opening file.');
	exit(1);
}

function process($input_handle) {
	$got_title = false;
	$got_date = false;
	$got_fields = false;
	$values = null;

	assert_options(ASSERT_BAIL, true);

	while (($line = fgets($input_handle)) !== false) {
		$line = trim($line);

		if (!$line) {
			continue;
		}

		if (starts_with('OnGuard', $line)) {
			stderr('Got guard.');
			$got_fields = false;
			$got_date = false;
			continue;
		}

		if (starts_with('Visit History', $line)) {
			stderr('Got title.');
			$got_title = true;
			$got_date = false;
			continue;
		}

		if (starts_with('Report Date', $line)) {
			stderr('Got date.');

			assert('$got_title');

			$got_date = true;
			continue;
		}

		assert('$got_date');

		if (starts_with('Actual', $line)) {
			stderr('Got first fields row.');
			continue;			
		}

		if (starts_with('Visitor Name', $line)) {
			stderr('Got fields.');
			$got_fields = true;
			$got_values = false;

			$visitor_name_pos = 0;
			$visit_type_pos = mb_strpos($line, 'Visit Type');
			$host_pos = mb_strpos($line, 'Host');
			$organization_pos = mb_strpos($line, 'Organization');
			$time_in_pos = mb_strpos($line, 'Time In');
			$time_out_pos = mb_strpos($line, 'Time Out');
			continue;
		}

		assert('$got_fields');

		//stderr('Line: ' . $line);
		//stderr('Visit type position: ' . $visit_type_pos);

		//$line = preg_match('/^(?<visitor_surname>[a-záéíñóú\s\.]+,)\s(?<visitor_name>[a-záéíñóú\s]+)$/i', $line, $matches, PREG_OFFSET_CAPTURE);

		if (!$values) {
			$values = array();
			$values['name'] = '';
			$values['visit_type'] = '';
			$values['host'] = '';
			$values['organization'] = '';
			$values['time_in'] = '';
			$values['time_out'] = '';
		}

		stderr('Processing values.');

		while (($line = fgets($input_handle)) !== false) {
			$line = trim($line);

			stderr('Iterating value line.');

			if ($line) {
				$got_values = true;
				stderr('Parsing value line.');
			} else {
				if (!$got_values) {
					stderr('Skipping to next line.');
					continue;
				}

				foreach ($values as $key => $value) {
					$values[$key] = trim($value);
				}

				stderr('Outputting values.');
				stderr('----------------------------------------------------------------------------------------------');
				stdout(implode("\t", $values));
				stderr('----------------------------------------------------------------------------------------------');
				$values = null;
				$got_values = false;

				break 1;
			}

			$values['name'] .=  ' ' . trim(mb_substr($line, $visitor_name_pos, $visit_type_pos));
			$values['visit_type'] .= ' ' . trim(mb_substr($line, $visit_type_pos, $host_pos - $visit_type_pos));
			$values['host'] .= ' ' . trim(mb_substr($line, $host_pos, $organization_pos - $host_pos));
			$values['organization'] .= ' ' . trim(mb_substr($line, $organization_pos, $time_in_pos - $organization_pos));
			$values['time_in'] .= ' ' . trim(mb_substr($line, $time_in_pos, $time_out_pos - $time_in_pos));
			$values['time_out'] .= ' ' . trim(mb_substr($line, $time_out_pos));
		}
	}
}

function starts_with($needle, $haystack) {
	return strpos($haystack, $needle) === 0;
}

function stderr($string) {
	file_put_contents('php://stderr', $string . PHP_EOL, FILE_APPEND);
}

function stdout($string) {
	echo $string, PHP_EOL;
}

?>
