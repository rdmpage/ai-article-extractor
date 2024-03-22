<?php


//----------------------------------------------------------------------------------------
// https://www.php.net/manual/en/function.mb-str-split.php
if( !function_exists('mb_str_split')){
    function mb_str_split(  $string = '', $length = 1 , $encoding = null ){
        if(!empty($string)){ 
            $split = array();
            $mb_strlen = mb_strlen($string,$encoding);
            for($pi = 0; $pi < $mb_strlen; $pi += $length){
                $substr = mb_substr($string, $pi,$length,$encoding);
                if( !empty($substr)){ 
                    $split[] = $substr;
                }
            }
        }
        return $split;
    }
}

//----------------------------------------------------------------------------------------
// A simple multibyte-safe case-insensitive string comparison:
// https://snippets.cacher.io/snippet/fcfff8a3cbf83fa3b909
function mb_strcasecmp($str1, $str2, $encoding = null) {
  if (null === $encoding) { $encoding = mb_internal_encoding(); }
  return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
}

// Smith-Waterman alignemnt, based on https://en.wikipedia.org/wiki/Smith–Waterman_algorithm

//----------------------------------------------------------------------------------------
function swa($seq1, $seq2, $label1 = 'one', $label2 = 'two', $debug = false)
{
	$alignment = new stdclass;
	$alignment->labels = array($label1, $label2);
	
	$alignment->strings = array();
	$alignment->strings[] = $seq1;
	$alignment->strings[] = $seq2;
	
	$alignment->text = array();
	$alignment->spans = array(
		array(),
		array()
		);
	$alignment->score = 0.0;
	
	
	// Weights
	$match 		=  3;
	$mismatch 	= -1;
	$deletion 	= -3;
	$insertion 	= -3;
	
	// Tokenise input strings
	$X = mb_str_split($seq1);
	$Y = mb_str_split($seq2);
	
	// Lengths of strings
	$m = count($X);
	$n = count($Y);
	
	// Create and initialise matrix for dynamic programming
	$H = array();
	
	for ($i = 0; $i <= $m; $i++)
	{
		$H[$i][0] = 0;
	}
	for ($j = 0; $j <= $n; $j++)
	{
		$H[0][$j] = 0;
	}
	
	// Store which cell is the origin of the value in cell [i,j]
	// Possble values are ↖ (diagonal), ↑ (up), and ← (left)
	$P = array();
	for ($i = 0; $i <= $m; $i++)
	{
		$P[$i] = array();
		for ($j = 0; $j <= $n; $j++)
		{
			$P[$i][$j] = ' ';
		}
	}
	
	// Do alignment
			
	$max_i = 0;
	$max_j = 0;
	$max_H = 0;
	
	for ($i = 1; $i <= $m; $i++)
	{
		for ($j = 1; $j <= $n; $j++)
		{		
			$a = $H[$i-1][$j-1];
			
			$s1 = $X[$i-1];
			$s2 = $Y[$j-1];
			
			// Compute score of four possible situations (match, mismatch, deletion, insertion)
			if (mb_strcasecmp ($s1, $s2) === 0)
			{
				// Strings are identical
				$a += $match;
			}
			else
			{
				// Strings are different
				$a += $mismatch; // you're either the same or you're not
			}
		
			$b = $H[$i-1][$j] + $deletion;
			$c = $H[$i][$j-1] + $insertion;
			
			// Get maximum value, and store relatve direction of cell that contributes 
			// to max value
			$max = 0;
			$pred = ' ';
			
			if ($a > $max)
			{
				$max = $a;
				$pred = '↖';
			}

			if ($b > $max)
			{
				$max = $b;
				$pred = '↑';
			}
			
			if ($c > $max)
			{
				$max = $c;
				$pred = '←';
			}
						
			$H[$i][$j] = $max;			
			$P[$i][$j] = $pred;
						
			if ($H[$i][$j] > $max_H)
			{
				$max_H = $H[$i][$j];
				$max_i = $i;
				$max_j = $j;
			}
		}
	}
	
	// Best possible score is perfect alignment with no mismatches or gaps
	$maximum_possible_score = min(count($X), count($Y)) * $match;
	$alignment->score = $max_H / $maximum_possible_score;
	
	if ($debug)
	{
		echo "\nH\n";
		for ($i = 0; $i <= $m; $i++)
		{
			echo str_pad($i, 3, ' ', STR_PAD_RIGHT) . "|";
		
			for ($j = 0; $j <= $n; $j++)
			{
				echo str_pad($H[$i][$j], 4, ' ', STR_PAD_LEFT);
			}
		
			echo "\n";
		}
	}
	
	if ($debug)
	{
		echo "\nP\n";
		for ($i = 0; $i <= $m; $i++)
		{
			echo str_pad($i, 3, ' ', STR_PAD_RIGHT) . "|";
		
			for ($j = 0; $j <= $n; $j++)
			{
				echo '   ' . $P[$i][$j];
			}		
			echo "\n";
		}
	}
			
	// Traceback to recover alignment
	$value = $H[$max_i][$max_j];
	$i = $max_i;
	$j = $max_j;
	
	$alignment->spans[0][1] = $max_i - 1;
	$alignment->spans[1][1] = $max_j - 1;
		
	while ($value != 0)
	{
		if ($P[$i][$j] == '↖')
		{
			// echo "↖ i=$i, j=$j, " . $X[$i-1] . ' ' . $Y[$j-1] . ' ' . $H[$i][$j] . "\n";
		
			$rows[0][] = $X[$i-1];
			$rows[2][] = $Y[$j-1];
			
			if (mb_strcasecmp($X[$i-1], $Y[$j-1]) === 0) {
				$rows[1][] = "|";
			} else {
				$rows[1][] = " ";
			}	
			
			$i--;
			$j--;	
		}
		else
		{
			if ($P[$i][$j]  == '↑')
			{
				// echo "↑ i=$i, j=$j, " . $X[$i-1] . ' ' . $Y[$j-1] . ' ' . $H[$i][$j] . "\n";
			
				$rows[0][] = $X[$i-1];
				$rows[1][] = " ";
				$rows[2][] = '-';
			
				$i--;
			}
			elseif ($P[$i][$j]  == '←')
			{
				// echo "← i=$i, j=$j, " . $X[$i-1] . ' ' . $Y[$j-1] . ' ' . $H[$i][$j] . "\n";
						
				$rows[0][] = '-';
				$rows[1][] = " ";
				$rows[2][] = $Y[$j-1];
			
				$j--;
			}
		}
		$value = $H[$i][$j];
	}
	
	$alignment->spans[0][0] = $i;
	$alignment->spans[1][0] = $j;
	
	
	// traceback gives us the alignment in reverse order
	
	$rows[0] = array_reverse($rows[0]);
	$rows[1] = array_reverse($rows[1]);
	$rows[2] = array_reverse($rows[2]);	

	$alignment->text = array(
		join('', $rows[0]),
		join('', $rows[1]),
		join('', $rows[2])
	);
		
	// print_r($alignment);
	
	return $alignment;
}



?>
