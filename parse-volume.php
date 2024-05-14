<?php

error_reporting(E_ALL);

//----------------------------------------------------------------------------------------
function is_roman($text)
{
	return preg_match('/^[IVXLC]+$/i', $text);
}

///----------------------------------------------------------------------------------------
// Parse BHL volume info into structured data (cf. BioStor classic)
function parse_volume($text)
{
	// pattern fragments
	
	// volume
	$volume_prefix = '([V|v](ol)?|t|d)\.?\s*';
	$part_prefix = '[P|p]t\.\s*';
	$fasc_prefix = 'fasc\.\s*';
	$band_prefix = 'Bd\.\s*';
	$number_prefix = '[N|n]o\.\s*';
	
	$volume_number_pattern = '(?<volume1>\d+)(-(?<volume2>\d+))?';	
	
	$volume_pattern_1 = $volume_prefix . $volume_number_pattern;
	
	// volume is just a number
	$volume_one_number = '(?<volume>\d+)';
	
	// volume is exactly a year, i.e. ^[0-9]{4}$
	$volume_pattern_3 = '^(?<volume1>[0-9]{4})$';
	
	// volume Roman
	$volume_roman_pattern = '(?<volume>[IVXLCivxlc]+)';
	
	// volume is a part
	//$volume_part_pattern_1 = '^' . $part_prefix . '\s*' . $volume_number_pattern;
	//$volume_part_pattern_2 = '^' . $part_prefix . '\s*' . $volume_number_range_pattern;
	
	
	// Proc Zool Soc London
	// 1921:pt.3-4 [pp.447-887]
	// 1901:v.2 (May-Dec.)
	
	// series
	$series_pattern = 'ser\.(?<series>\d+)';

	// issue
	$issue_pattern = '(no|pt|Heft|fasc)\.((?<issue>\d+)|(?<issue1>\d+)-(?<issue2>\d+))';		

	// no. 1-no. 2
	$issue_pattern_two = '(' . $number_prefix . '(?<issue1>\d+)\s*-\s*' . $number_prefix . '(?<issue2>\d+)' . ')';		
	
	// supplement 
	$supplement_pattern = '(?<issue>Suppl\.?)';

	// separator
	$volume_issue_separator = '[:|=]';

	// dates
	// year in parentheses
	$date_pattern_one_year = '(\s*\((?<date>(?<y1>[0-9]{4}))\))';

	// pair of years 
	$date_pattern_two_years = '(\s*\((?<date>(?<y1>[0-9]{4})-(?<y2>[0-9]{4}))\))';

	// pair of years 
	$date_pattern_long_short_years = '(\s*\((?<date>(?<y1>[0-9]{4})-(?<y2>[0-9]{2}))\))';

	// year with month
	$date_pattern_year_month = '(\s*\((?<date>(?<y1>[0-9]{4}):(?<m1>\w+\.?))\))';
	
	// pair of months
	$date_pattern_two_months = '(?<m1>\w+\.?)-(?<m2>\w+\.?)';
	
	// year and two months
	$date_pattern_year_two_months = '(\s*\((?<date>(?<y1>[0-9]{4}):' . $date_pattern_two_months . ')\))';


	$date_pattern_year_month_twice = '(\s*\((?<date1>(?<y1>[0-9]{4}):(?<m1>\w+\.?)-(?<y2>[0-9]{4}):(?<m2>\w+\.?))\))';

	
	// one year
	$year_pattern = '(?<y1>[0-9]{4})';

	// combinations of patterns
	$patterns = array(
	
		// v.39 (1933-36)
		'/^' . $volume_pattern_1 . $date_pattern_long_short_years . '/',
	
		// "v.25:no.1-4;v.26:no.1-4 (2002-2003)",
		'/' . $volume_prefix . '(?<volume1>\d+)(:.*);' . $volume_prefix . '(?<volume2>\d+)' . '(:.*)' . $date_pattern_two_years . '/',
	
		'/^' . $part_prefix . $volume_number_pattern . $date_pattern_one_year . '/',
	
		'/^' . $part_prefix . $volume_number_pattern . $date_pattern_one_year . '/',
		'/^' . $part_prefix . $volume_number_pattern . $date_pattern_two_years . '/',
	
	
		'/^' . $volume_pattern_1 . '(' . $volume_issue_separator . $issue_pattern . ')?' . $date_pattern_one_year . '/',
		'/^' . $volume_pattern_1 . '(' . $volume_issue_separator . $issue_pattern . ')?' . $date_pattern_two_years . '/',
		'/^' . $volume_pattern_1 . '(' . $volume_issue_separator . $issue_pattern . ')?' . $date_pattern_year_month . '/',

		'/^' . $volume_pattern_1 . $date_pattern_two_years. '/',
				
		'/' . $volume_pattern_3 . '/',
		
		'/^' . $volume_one_number . $volume_issue_separator . $issue_pattern . '/',
		'/^' . $volume_one_number . $volume_issue_separator . $date_pattern_two_months . '/',

		// // 1901:v.2 (May-Dec.)
		'/^' . $year_pattern . $volume_issue_separator . $volume_prefix . $volume_one_number . '\s*' . '\(' . $date_pattern_two_months . '\)' . '/',
		
		// fasc.
		'/^' . $fasc_prefix . $volume_number_pattern . $date_pattern_one_year . '/',
		'/^' . $fasc_prefix . $volume_number_pattern . $date_pattern_two_years . '/',
		
		// band
		'/^' . $band_prefix . $volume_one_number . $date_pattern_one_year . '/',
		'/^' . $band_prefix . $volume_one_number . $date_pattern_two_years . '/',
		'/^' . $band_prefix . $volume_number_pattern . $date_pattern_two_years . '/',

		'/^' . $band_prefix . $volume_number_pattern . $volume_issue_separator . $issue_pattern . $date_pattern_year_month . '/',

		// Bd.35 (1921:Apr.-Dec.)
		'/^' . $band_prefix . $volume_number_pattern . $date_pattern_year_two_months . '/',
		// Bd.28:Heft.1-2,4 (1914:Mar.-Dec.)
		'/^' . $band_prefix . $volume_number_pattern . $volume_issue_separator . $issue_pattern . $date_pattern_year_two_months . '/',

		// Bd.24 (1910:Jan.-1911:Jan.)
		'/^' . $band_prefix . $volume_number_pattern . $date_pattern_year_month_twice . '/',
		
		// Bd.5:pt.1 (1907-1924)
		'/^' . $band_prefix . $volume_number_pattern . $volume_issue_separator . $issue_pattern . $date_pattern_two_years . '/',
		
		// Suppl.:Bd.2 (1933)
		'/^' . $supplement_pattern . $volume_issue_separator . $band_prefix . $volume_number_pattern . $date_pattern_one_year . '/',
		
		// [v.12]=[no.45-48] (1887-1888)
		'/^' . '\[' . $volume_prefix . $volume_number_pattern . '\]' . $volume_issue_separator . '\[' . $issue_pattern . '\]' . $date_pattern_two_years . '/',		
		
		'/^' . 'pt.' . $volume_roman_pattern . $volume_issue_separator . '\s*' . $issue_pattern . '/',

		// "ser.5:v.11=no.61-66 (1883)",
		'/^' . $series_pattern . $volume_issue_separator . $volume_prefix . $volume_one_number . $volume_issue_separator . $issue_pattern . $date_pattern_one_year . '/',

		// no.71 (2019)
		'/^' . $number_prefix . $volume_number_pattern . $date_pattern_one_year . '/',

		// ser.2:d.7 (1901-1902)
		'/^' . $series_pattern . $volume_issue_separator . $volume_prefix . $volume_one_number . $date_pattern_two_years . '/',
		
		// Vol. 5 : no. 1-no. 2 (1986)
		'/^' . $volume_pattern_1 . '\s*' . $volume_issue_separator . '\s*' . $issue_pattern_two . $date_pattern_one_year . '/',


		// fallbacks
		'/^' . $volume_number_pattern . '$/',

		'/^' . $volume_pattern_1 . '/',	
	);
	
	if (0)
	{
		print_r($patterns);
	}

	$num_patterns = count($patterns);


	$obj = new stdclass;
	$obj->text = $text;

	$matched = false;	
	$matches = array();

	$i = 0;

	while ($i < $num_patterns && !$matched)
	{
		if (preg_match($patterns[$i], $text, $matches))
		{
			$obj->pattern = $patterns[$i];
			$matched = true;
		}
		else
		{
			$i++;
		}
	}

	if (!$matched)
	{
		$obj->parsed = false;
	}
	else
	{
		$obj->parsed = true;
			
		foreach ($matches as $k => $v)
		{
			if (is_numeric($k))
			{
		
			}
			else
			{
				if ($v != '')
				{
					switch ($k)
					{
						case 'series':
							$obj->{'collection-title'} = array($v);
							break;
					
						case 'issue':
							$obj->{$k} = array($v);
							break;
							
						case 'issue1':
							if (!isset($obj->issue))
							{
								$obj->issue = array();
							}							
							$obj->issue[] = $v;
							break;
													
						case 'issue2':
							// generate range of issue values 
							$from = $obj->issue[0];
							$to = $v;
							
							if (is_numeric($from) && is_numeric($to))
							{
								for ($i = $from + 1; $i <= $to; $i++)
								{
									$obj->issue[] = (String)$i;
								}
							}
							else
							{	// not a numerical range so just add value												
								$obj->issue[] = $v;
							}
							break;													
						
						case 'volume':
							if (!isset($obj->volume))
							{
								$obj->volume = array();
							}		
							
							if (is_roman($v))
							{
								$v = arabic($v);
							}
												
							$obj->volume[] = $v;
							break;
													
						case 'volume1':
							if (!isset($obj->volume))
							{
								$obj->volume = array();
							}							
							$obj->volume[] = $v;
							break;
													
						case 'volume2':
							// generate range of volume values 
							$from = $obj->volume[0];
							$to = $v;
							
							if (is_numeric($from) && is_numeric($to))
							{
								for ($i = $from + 1; $i <= $to; $i++)
								{
									$obj->volume[] = (String)$i;
								}
							}
							else
							{	// not a numerical range so just add value												
								$obj->volume[] = $v;
							}
							break;						
						
						case 'm1':
							if (!isset($obj->issued))
							{
								$obj->issued = new stdclass;
								$obj->issued->{'date-parts'} = array();
							}
							
							if (!isset($obj->issued->{'date-parts'}[0][0]) && isset($obj->volume[0]))
							{
								if (preg_match('/^' . $year_pattern . '$/', $obj->volume[0]))
								{
									$obj->issued->{'date-parts'}[0][0] = (Integer)$obj->volume[0];
								}
							}
							
							if (isset($obj->issued->{'date-parts'}[0][0]))
							{
								$time = strtotime($v);
								if ($time === false)
								{							
								}
								else
								{
									$month = date("n", $time);
									$obj->issued->{'date-parts'}[0][1] = (Integer)$month;
								}
							}
							break;
							
							// month is either extension of range for first year, or belongs with second
						case 'm2':
							$time = strtotime($v);
							if ($time === false)
							{							
							}
							else
							{
								$month = date("n", $time);
						
								if (isset($obj->issued) 
									&& isset($obj->issued->{'date-parts'}[0][0])
								)
								{
									// we have year 2
									if (!isset($obj->issued->{'date-parts'}[1][0]))
									{
										// no year 2 so extend range for year 1
										$obj->issued->{'date-parts'}[1][0] = $obj->issued->{'date-parts'}[0][0];
									}
									$obj->issued->{'date-parts'}[1][1] = (Integer)$month;
								}
							}
							break;
							
						
						case 'y1':
							if (!isset($obj->issued))
							{
								$obj->issued = new stdclass;
								$obj->issued->{'date-parts'} = array();
							}
							$obj->issued->{'date-parts'}[0][0] = (Integer)$v;
							break;						
					
						case 'y2':
							if (!isset($obj->issued))
							{
								$obj->issued = new stdclass;
								$obj->issued->{'date-parts'} = array();
							}
							// if two digits long assume its a truncated range
							if (strlen($v) == 2)
							{
								if (isset($obj->issued->{'date-parts'}[0][0]))
								{
									$century = floor($obj->issued->{'date-parts'}[0][0]/100);
									$v = $century . $v;
								}
							}
							
							$obj->issued->{'date-parts'}[1][0] = (Integer)$v;
							break;
						
						default:
							break;
					}
				}
			}
		}
	}
	
	return $obj;
}
	
//----------------------------------------------------------------------------------------
// "tests"
if (0)	
{

	$input = array(
	/*
		'v.101 (2004)',
		'v.106:no.3 (2009)',
		'v.28:pt.3-4 (1922)',
		'v.104:no.3 (2007:Dec.)',
		'v.31:pt.3-4 (1926-1927)',
		'v.106:no.2 (2009:Aug.)',
		'v.106:no.1 (2009:Apr.)',
	*/


	"v.45 (1944-1945)",
	"Index:v.25-30 (1928)",
	"v.34:pt.1-2 (1930)",
	"v.26 (1918-1921)",
	"v.105:no.1 (2008:Apr.)",
	"v.97 (2000)",
	"v.71 (1974)",
	"v.99 (2002)",
	"v.106:no.3 (2009)",
	"v.106:no.2 (2009:Aug.)",
	"v.108-109 (2011-2012)",
	"v.41:pt.1-2 (1939)",
	"v.58 (1961)",
	"v.62 (1965)",
	"v.84 (1987)",
	"v.9 (1894-1895)",
	"v.35:pt.3-4 (1932)",
	"v.43 (1942-1943)",
	"v.83 (1986:Dec.;Suppl.:1886-1986)",
	"v.68 (1971)",
	"v.14 (1902-1903)",
	"v.59 (1962)",
	"v.18 (1907-1908)",
	"v.105:no.3 (2008:Dec.)",
	"v.104:no.3 (2007:Dec.)",
	"v.36:pt.1-2 (1932-1933)",
	"v.106:no.1 (2009:Apr.)",
	"v.66 (1969)",
	"v.87 (1990)",
	"v.102:no.1 (2005:Apr.)",
	"v.21:pt.1-2 (1912)",
	"v.89 (1992)",
	"v.16 (1904-1906)",
	"v.40:pt.3-4 (1938-1939)",
	"v.52 (1954-1955)",
	"v.13 (1900-1901)",
	"v.40:pt.1-2 (1938)",
	"v.10 (1895-1897)",
	"v.57 (1960)",
	"v.38:pt.3-4 (1936)",
	"v.107 (2010)",
	"v.76 (1979)",
	"v.83 (1986:Apr-Aug)",
	"v.5 (1890)",
	"v.3 (1888)",
	"v.31:pt.3-4 (1926-1927)",
	"v.38:pt.1-2 (1935)",
	"v.65 (1968)",
	"v.63 (1966)",
	"v.33:pt.1-2 (1929)",
	"v.34:pt.3-4 (1930-1931)",
	"v.72 (1975)",
	"v.98 (2001)",
	"v.79 (1982)",
	"v.95 (1998)",
	"v.11 (1897-1898)",
	"v.1 (1886)",
	"v.80 (1983)",
	"v.7 (1892)",
	"v.60 (1963)",
	"v.92 (1995)",
	"v.27 (1920-1922)",
	"v.93 (1996)",
	"v.69;Index:v.43-53 (1972)",
	"v.31:pt.1-2 (1926)",
	"v.44 (1943-1944)",
	"v.42:pt.3-4 (1942)",
	"v.81 (1984)",
	"Index:v.37-42 (1949)",
	"v.70 (1973)",
	"v.33:pt.3-4 (1929)",
	"v.23 (1914)",
	"v.88 (1991)",
	"v.91:no.3 (1994)",
	"v.42:pt.1-2 (1942)",
	"v.49 (1950-1951)",
	"v.75 (1978:Apr.-Aug.)",
	"v.39:pt.1-2 (1936-1937)",
	"v.105:no.2 (2008:Aug.)",
	"Index:v.25-30 (1928)",
	"v.91:no.1 (1994)",
	"v.25 (1917-1918)",
	"v.56 (1959)",
	"v.48 (1948-1949)",
	"v.20 (1910-1911)",
	"v.64 (1967)",
	"v.29:pt.1-2 (1923)",
	"v.32:pt.1-2 (1927)",
	"Index:v.57,59-60 (1960,1962-1963)",
	"v.101 (2004)",
	"v.67 (1970)",
	"v.36:pt.3-4;Index:v.31-36 (1936)",
	"v.32:pt.3-4 (1928)",
	"v.41:pt.3-4 (1940)",
	"v.100 (2003)",
	"v.37:pt.3-4 (1934-1935)",
	"v.96 (1999)",
	"v.75 (1978:Dec.)",
	"v.6 (1891)",
	"v.102:no.3 (2005:Dec.)",
	"v.104:no.1 (2007:Apr.)",
	"v.55 (1958)",
	"v.30:pt.3-4 (1925)",
	"v.54:no.1-2 (1956-1957)",
	"v.77 (1980)",
	"v.54:no.3-4 (1957)",
	"v.24 (1915-1917)",
	"v.104:no.2 (2007:Aug.)",
	"v.39:pt.3-4 (1937)",
	"v.17 (1906-1907)",
	"v.12 (1898-1900)",
	"v.29:pt.3-4 (1924)",
	"v.19 (1909-1910)",
	"v.21:pt.3-5 (1912)",
	"v.22 (1913)",
	"v.53 (1955-1956)",
	"v.94 (1997)",
	"v.30:pt.1-2 (1924-1925)",
	"v.86 (1989)",
	"v.4 (1889)",
	"v.90 (1993)",
	"v.61 (1964)",
	"v.28:pt.1-2 (1921-1922)",
	"v.102:no.2 (2005:Aug.)",
	"v.28:pt.3-4 (1922)",
	"v.47 (1947-1948)",
	"v.35:pt.1-2 (1931)",
	"v.82 (1985)",
	"v.85 (1988)",
	"v.73 (1976)",
	"v.15 (1903-1904)",
	"v.74 (1977)",
	"v.51 (1952-1953)",
	"v.46 (1946-1947)",
	"v.2 (1887)",
	"v.50 (1951-1952)",
	"v.37:pt.1-2 (1934)",
	"v.8 (1893)",
	"v.78 (1981)"
	
	);

/*
	$input = array(
	'v.75 (1978:Apr.-Aug.)',
	'1922',
	"v.101 (2004)",
	"v.30:pt.1-2 (1924-1925)",
	
	"v.28:pt.1-2 (1921-1922)",
    "v.102:no.2 (2005:Aug.)",
    
    'pt.29-30 (1864)',
    'pt.36 (1866)',
    'pt.1-2 (1854-1855)',
	);
*/

	$input = array(

    'pt.29-30 (1864)',
    'pt.36 (1866)',
    'pt.1-2 (1854-1855)',
	);


	$input = array(
	'v.53 (1986)',
	'v.54:no.1 (1987:Mar.)',
	'v.54:no.2 (1987:Mar.)',
	'v.56 (1987-1988)',



	);
	
	// Muelleria
	$input = array(

'v.1:no.1 (1955:Aug.)',
'v.1:no.2 (1955:Dec.)',
'v.1:no.3 (1967:Jul.)',
'v.2:no.1 (1969:Mar.)',
'v.2:no.2 (1971:Aug.)',
'v.2:no.3 (1972:Nov.)',
'v.2:no.4 (1973:Apr.)',
'v.3:no.1 (1974:Jul.)',
'v.3:no.2 (1975:Jul.)',
'v.3:no.3 (1976:Dec.)',
'v.3:no.4 (1977:Sep.)',
'v.4:no.1 (1978:Jul.)',
'v.4:no.2 (1979:May.)',
'v.4:no.3 (1980:Apr.)',
'v.4:no.4 (1981:May.)',
'v.5:no.1 (1982:Mar.)',
'v.5:no.2-3 (1983:Mar.)',
'v.5:no.4-5 (1984:Mar.)',
'v.6:no.1-2 (1985:May.)',
'v.6:no.3-4 (1986:May.)',
'v.6:no.5 (1987:Apr.)',
'v.6:no.6 (1988:Feb.)',
'v.7:no.1 (1989:Apr.)',
'v.7:no.2 (1990:Mar.)',
'v.7:no.3 (1991:Mar.)',
'v.7:no.4 (1992:Apr.)',
'v.8:no.1 (1993:Apr.)',
'v.8:no.2 (1994:Mar.)',
'v.8:no.3 (1995:May.)',
'v.9 (1996)',
'v.10 (1997)',
'v.11 (1998)',
'v.12:no.1 (1999)',
'v.12:no.2 (1999)',
'v.13 (2000)',
'v.14 (2000)',
'v.15 (2001)',
'v.16 (2002)',
'v.17 (2003)',
'v.18 (2003)',
'v.19 (2004)',
'v.20 (2004)',
'v.21 (2005)',
'v.22 (2005)',
'v.23 (2006)',
'v.24 (2006)',
'v.25 (2007)',
'v.26:no.1 (2008)',
'v.26:no.2 (2008)',
'v.27:no.1 (2009)',
'v.27:no.2 (2009)',
'v.28:no.1 (2010)',
'v.28:no.2 (2010)',
'v.29:no.1 (2011)',
'v.29:no.2 (2011)',
'v.30:no.1 (2012)',
'v.30:no.2 (2012)',
'v.31 (2013)',
'v.32 (2014)',
'v.33 (2014-2015)',
'v.34 (2015-2016)',
'v.35 (2016-2017)',
'v.36 (2017-2018)',
'v.37 (2018-2019)',
'v.38 (2019-2020)',	);	

$input = array(
//'v.4:no.1-4 (1990-1992)'

'1870',
'1871',
'1912:pt.3-4 [pp.505-913]',
'1868',
'1891',
'1865',
'1901:v.2 (May-Dec.)',
'1884',
'1901:v.1 (Jan.-Apr.)',
'1893',
'1889',
'1893',
'1878:May-Dec.',
'1886',
'pt.28 (1860)',
'pt.12-15 (1844-1847)',
'pt.12-15 (1844-1847)',
'pt.12-15 (1844-1847)',
'pt.12-15 (1844-1847)',
'v.5 (1848-1860) [Plates:Mollusca]',
'1899',
'1905:v.1 (Jan.-Apr.)',
'1890',
'1916:pt.3-4 [pp.449-756]',
'1882',
'1876',
'1877:May-Dec.',
'1885',
'1907:Jan.-Apr. [pp.1-446]',
'1922:pt.3-4 [pp.483-1276]',
'1905:v.2 (May-Dec.)',
'Index (1861-1870)',
'1894',
'1882',
'pt.25 (1857)',
'pt.4-8 (1836-1840)',
'pt.4-8 (1836-1840)',
'pt.4-8 (1836-1840)',
'pt.4-8 (1836-1840)',
'pt.4-8 (1836-1840)',
'Index (1901-1910)',
'1872',
'1900',
'pt.27 (1859) [lacks plates]',
'1902:v.1 (Jan.-Apr.)',
'pt.21-23 (1853-1855)',
'pt.21-23 (1853-1855)',
'pt.21-23 (1853-1855)',
'1880',
'v.2 (1848-1854) [Plates:Aves]',
'pt.27 (1859) [lacks pg.149]',
'1892',
'pt.2-3 (1834-1835)',
'pt.2-3 (1834-1835)',
'pt.18-19 (1850-1851)',
'pt.18-19 (1850-1851)',
'1917:pt.1-4 [pp.1-338]',
'1911:pt.3-4 [pp.557-1213]',
'v.4 (1848-1860) [Plates:Reptilia et pisces]',
'1921:pt.1-2 [pp.1-446]',
'1903:v.2 (May-Dec.)',
'1895',
'1876:Jan.-Apr.',
'1915:pt.1-2 [pp.1-298]',
'1867',
'1873',
'1892',
'1914:pt.1-2 [pp.1-490]',
'1897',
'1904:v.2 (May-Dec.)',
'Index (1848-1860)',
'v.1 (1848-1860) [Plates:Mammalia]',
'1898',
'1923:pt.1-2 [pp.1-481]',
'1863',
'1862',
'pt.17 (1849)',
'1883',
'Index (1911-1920)',
'1909:May-Dec. [pp.545-952]',
'1899',
'1861',
'1912:pt.1-2 [pp.1-504]',
'1866',
'1910:Jan.-Mar. [pp.1-588]',
'v.6 (1848-1860) [Plates: Annulosa, Radiata]',
'1879:Jan.-Mar.',
'1889',
'1884',
'1910:Apr.-June [pp.589-1033]',
'1919:pt.1-4 [pp.1-499]',
'1905:v.2 (May-Dec.) [Incomplete]',
'pt.26 (1858)',
'1896',
'1888',
'Index (1891-1900)',
'1908:May-Dec. [pp.431-983]',
'1901:v.2 (May-Dec.)',
'1890',
'1921:pt.1-2 [pp.1-446]',
'1896',
'1906:Jan.-Apr. [pp.1-462] [Incomplete]',
'1875',
'1874',
'1909:Jan.-Apr. [pp.1-544]',
'1916:pt.1-2 [pp.1-448]',
'1879:Mar.-Dec.',
'1907:May-Dec. [pp.447-1121]',
'1883',
'1911:pt.1-2 [pp.1-555]',
'pt.1-6 (1833-1838)',
'pt.1-6 (1833-1838)',
'pt.1-6 (1833-1838)',
'pt.1-6 (1833-1838)',
'pt.1-6 (1833-1838)',
'pt.1-6 (1833-1838)',
'1878:Jan.-Apr.',
'1920:pt.1-4 [pp.1-656]',
'pt.19-20 (1851-1852)',
'pt.19-20 (1851-1852)',
'1922:pt.1-2 [pp.1-481]',
'1904:v.1 (Jan.-Apr.)',
'Index (1881-1890)',
'1918:pt.1-4 [pp.1-310]',
'1912:pt.3-4 [pp.505-913]',
'1902:v.2 (May-Dec.)',
'pt.18 (1850)',
'pt.24-25 (1856-1857)',
'pt.24-25 (1856-1857)',
'pt.16-17 (1848-1849)',
'pt.16-17 (1848-1849)',
'1917:pt.1-4 [pp.1-338]',
'1869',
'1891',
'1862',
'v.3 (1855-1860) [Plates:Aves]',
'1906:May-Dec. [pp.463-1052]',
'1887',
'1914:pt.3-4 [pp.491-1077]',
'1913:pt.1-2 [pp.1-337]',
'1906:Jan.-Apr. [pp.1-462]',
'1918:pt.1-4 [pp.1-310]',
'1885',
'1888',
'1915:pt.3-4 [pp.299-712]',
'Index (1871-1880)',
'1921:pt.3-4 [pp.447-887]',
'1923:pt.3-4 [pp.483-1097]',
'1900',
'1908:Jan.-Apr. [pp.1-430]',
'1912:pt.3-4 [pp.505-913]',
'1920:pt.1-4 [pp.1-656]',
'1877:Jan.-Apr.',
'1864',
'1886',
'1881',
'Index (1830-1847)',
'1903:v.1 (Jan.-Apr.)',
'pt.9-11 (1841-1843)',
'pt.9-11 (1841-1843)',
'pt.9-11 (1841-1843)',
'1867:May-Dec.',
'1913:pt.3-4 [pp.339-1104]',
'1894',
'1887'
);

// small cases to work on
if (0)
{
	$input=array(
	'1878:May-Dec.',
	'1901:v.2 (May-Dec.)',
	);
}


if (0)
{
	// test cases
	$input = array(
	'1878:May-Dec.',
	'v.15 (2001)',
	'v.34 (2015-2016)',
		'pt.36 (1866)',
		'pt.1-2 (1854-1855)',
	'v.5:no.2-3 (1983:Mar.)',
	'1901:v.2 (May-Dec.)',

	);
}

$input=array(
"fasc.1-11 (1902-1903)",
"fasc.12-14 (1903)",
"fasc.15-19 (1904)",
"fasc.17a-d (1903-1904)",
"fasc.20-24 (1904)",
"fasc.25 (1904)",
"fasc.26 (1905)",
"fasc.27 (1905)",
"fasc.28 (1905)",
"fasc.29 (1905)",
"fasc.30 (1905)",
"fasc.32-39 (1905-1906)",
"fasc.40-46 (1906)",
"fasc.47-54 (1906-1907)",
"fasc.53 (1907)",
"fasc.55 (1907)",
"fasc.56 (1907)",
"fasc.57 (1907)",
"fasc.58 (1907)",
"fasc.59 (1907)",
"fasc.60 (1907)",
"fasc.61-64 (1908)",
"fasc.65-75 (1908)",
"fasc.76-81 (1908)",
"fasc.82-86 (1908-1910)",
"fasc.87 (1908)",
"fasc.88 (1909)",
"fasc.89 (1909)",
"fasc.90 (1909)",
"fasc.91 (1909)",
"fasc.92 (1909)",
"fasc.93 (1909)",
"fasc.94-97 (1909)",
"fasc.98-107 (1910)",
"fasc.108-111 (1910)",
"fasc.112 (1910-1911)",
"fasc.113 (1911)",
"fasc.114 (1911)",
"fasc.115 (1911)",
"fasc.116 (1911)",
"fasc.117 (1911)",
"fasc.118 (1911)",
"fasc.119 (1911)",
"fasc.120 (1911)",
"fasc.121 (1911)",
"fasc.122-127 (1913)",
"fasc.128 (1912)",
"fasc.129-134 (1913)",
"fasc.135 (1912)",
"fasc.136 (1912)",
"fasc.137 (1912)",
"fasc.138 (1912)",
"fasc.138 (1912)",
"fasc.139 (1912)",
"fasc.140 (1912)",
"fasc.140 (1912)",
"fasc.141 (1912)",
"fasc.142 (1912)",
"fasc.143 (1912)",
"fasc.144-151 (1913)",
"fasc.152 (1913)",
"fasc.153 (1913)",
"fasc.154 (1913)",
"fasc.155 (1914)",
"fasc.156 (1914)",
"fasc.157-163 (1914)",
"fasc.164-169 (1914-1916)",
"fasc.170-173 (1919-1921)",
"fasc.172 (1919)",
"fasc.174a-c (1921-1922)",
"fasc.175 (1921)",
"fasc.176-180 (1921-1922)",
"fasc.178 (1921)",
"fasc.184-185 (1925-1927)",
"fasc.208-209 (1950-1952)",
"fasc.20, 31, 36, 37, 39, 63, 169 (1904-1916)",
"Index:fasc.1-218 (1987-1988)",
"65-75",
"76-81",
"47-54",
"98-107",
"135-143",
"122-128",
"55-60",
"217a-b",
"25-31",
"20-24",
"112",
"87-93",
"113-112",
"32-39",
"164-169",
"181-183",
"204",
"196-203",
"170-173",
"174-175",
"15-19",
"206-207",
"12/14/17",
"157-163",
"193-195",
"176-180",
"108-111",
"152-156",
"205",
"210-216",
"217c-219",
"186-192"
);

$input=array(
"v.1 (1908-1909)",
"v.2 (1909-1910)",
"v.3 (1911-1912)",
"v.4-5 (1913-1917)",
"v.6-7 (1917-1921)",
"v.8 (1921-1922)"
);

$input=array(
"Bd.15 (1902)",
"Bd.16 (1903)",
"Bd.17 (1904)",
"Bd.18 (1905)",
"Bd.19 (1906)",
"Bd.20 (1907)",
"Bd.21 (1908)",
"Bd.22 (1909)",
"Bd.23 (1909-1912)",
"Bd.23 (1909)",
"Bd.24 (1910:Jan.-1911:Jan.)",
"Bd.25 (1911:Jan.-1912:Jan.)",
"Bd.26 (1912:Apr.-1912:Dec.)",
"Bd.27 (1913:Mar.-1914:Jan.)",
"Bd.28 (1914)",
"Bd.28:Heft.1-2,4 (1914:Mar.-Dec.)",
"Bd.29 (1915:May-1916:Jan.)",
"Bd.30 (1916:May-1917:Mar.)",
"Bd.31 (1917:June-1918:Feb.)",
"Bd.32 (1917:June-1918:Feb.)",
"Bd.33 (1919:June-Dec.)",
"Bd.34 (1920:June-Dec.)",
"Bd.35 (1921:Apr.-Dec.)",
"Bd.36:Heft.1-2 (1922:May)",
"Bd.36:Heft.3-4 (1922:Nov.)",
"Bd.37-38 (1923-1924)",
"Bd.39-40 (1925-1926)"
);

/*
$input = array(
"Bd.24 (1910:Jan.-1911:Jan.)",
);
*/

if (0)
{
	// test cases
	$input = array(
	
	'1878:May-Dec.',
	'1901:v.2 (May-Dec.)',
	
	'v.15 (2001)',
	'v.34 (2015-2016)',
	"v.6-7 (1917-1921)",	
	'v.5:no.2-3 (1983:Mar.)',
	
	'pt.36 (1866)',
	'pt.1-2 (1854-1855)',
	
	
	"Bd.35 (1921:Apr.-Dec.)",
	"Bd.36:Heft.1-2 (1922:May)",
	"Bd.28:Heft.1-2,4 (1914:Mar.-Dec.)",

	"fasc.175 (1921)",
	"fasc.176-180 (1921-1922)",

	);
}

$input=array(
"v.1=[no.1-4] (1875-1877)",
"v.2=[no.5-8] (1877-1878)",
"v.3=[no.9-12] (1878-1879)",
"v.4=[no.13-16] (1879-1880)",
"v.5=[no.17-20] (1880-1881)",
"v.6=[no.21-24] (1881-1882)",
"v.7=[no.25-28] (1882-1883) [Incomplete]",
"v.8=[no.29-32] (1883-1884)",
"v.9=[no.33-36] (1884-1885) [Incomplete]",
"v.9=[no.36] (1884-1885) [Incomplete]",
"v.10=[no.37-40] (1885-1886)",
"List of Contributors v.1-10 (1887)",
"[v.11]=[no.41-44] (1886-1887) [Incomplete]",
"[v.12]=[no.45-48] (1887-1888)",
"[v.13]=[no.49-50] (1888-1889)",
"[v.13]=[no.51-52] (1888-1889)",
"[v.14]=[no.53-56] (1889-1890)",
"[v.15]=[no.57-60] (1890-1891)",
"[v.16]=[no.61-64] (1891-1892) [Incomplete]",
"[v.17]=[no.65-68] (1892-1893)",
"[v.18]=[no.69-72] (1893-1894)",
"[v.19]=[no.73-76] (1894-1895) [Incomplete]",
"[v.20]=[no.77-80] (1895-1896)",
"[v.20]=[no.77-80];no.79:suppl. (1895-1896) [Incomplete]",
"v.21=no.81-84;no.81:suppl. (1896-1897)",
"v.22=no.85-88 (1897-1898)",
"v.23=no.89-92 (1898-1899)",
"v.24=no.93-96 (1899-1900)",
"v.25=no.97-100 (1900-1901)",
"v.26=no.101-104 (1901-1902)",
"v.27=no.105-108;no.107:suppl. (1902-1903)",
"v.28=no.109-112 (1903-1904) [Incomplete]",
"v.29=no.113-116 (1904-1905)",
"v.30=no.117-120;no.117:suppl.;no.120:suppl. (1905-1906)",
"v.31=no.121-124 (1906-1907)",
"v.32=no.125-128 (1907-1908)",
"v.33=no.129-132 (1908-1909)",
"v.34=no.133-136 (1909-1910) [Incomplete]",
"v.35=no.137-140 (1910-1911) [Incomplete]",
"v.36=no.141-144 (1911-1912)",
"v.37=no.145-[148] (1912) [Incomplete]",
"v.37=no.[148] (1912-1913)",
"v.38=no.149-152 (1913-1914) [Incomplete]",
"v.39=no.153-156 (1914-1915)",
"v.40=no.157-160 (1915-1916)",
"v.41=no.161-164 (1916-1917)",
"v.42=no.165-168 (1917-1918)",
"v.43=no.169-172 (1918-1919)",
"v.44=no.173-176 (1919-1920) [Incomplete]",
"v.45=no.177-180 (1920-1921)",
"v.46=no.181-184 (1921)",
"v.47 (1922)",
"v.47=no.185-189 (1922-1923) [Incomplete]",
"v.48=no.190-194 (1923-1924)",
"v.49=no.195-199 (1924-1925) [Incomplete]",
"v.50=no.200-204 (1925-1926) [Incomplete]",
"Index v.1-v.50 (1929)",
"v.51=no.205-209 (1926-1927)",
"v.52=no.210-214 (1927-1928)",
"v.53=no.215-220 (1928-1929) [Incomplete]",
"v.54=no.221-226 (1929-1930)",
"v.55=no.227-232 (1930-1931)",
"v.56=no.233-238 (1931)",
"v.57=no.239-244 (1932)",
"v.58=no.245-250 (1933)",
"v.59=no.251-256 (1934)",
"v.60=no.257-262 (1935) [Incomplete]",
"v.61=no.263-268 (1936)",
"v.62=no.269-274 (1937)",
"v.63=no.275-280 (1938)",
"v.64=no.281-286 (1939)",
"v.65=no.287-292 (1940)",
"v.66=no.293-298 (1941)",
"v.67=no.299-304 (1942)",
"v.68=no.305-310 (1943)",
"v.69=no.311-316 (1944)",
"v.70=no.317-322 (1945-1946)",
"v.71=no.323-328 (1946-1947)",
"v.72=no.329-334 (1947-1948) [Incomplete]",
"v.73=no.335-340 (1948-1949)",
"v.74=no.341-346 (1949)",
"v.75=no.347-352 (1950)",
"v.76=no.353-358 (1951-1952)",
"v.77=no.359-364 (1952-1953) [Incomplete]",
"v.78=no.365-370 (1953-1954)",
"v.79=no.371-376 (1954-1955)",
"v.80=no.377-379 (1955-1956)",
"v.81=no.380-382 (1956-1957)",
"v.82=no.383-385 (1957-1958)",
"v.83=no.386-388 (1958-1959)",
"v.84=no.389-391 (1959-1960)",
"v.84-85=no.389-394 (1959-1961) [Incomplete]",
"v.85=no.392-393 (1960-1961)",
"v.86=no.395-397 (1961-1962)",
"v.87=no.398-400 (1962-1963)",
"v.88=no.401-403 (1963-1964)",
"v.89=no.404-406 (1964-1965)",
"v.90=no.407-409 (1965-1966)",
"v.91=no.410-412 (1966-1967)",
"v.92=no.413-415 (1967-1968)",
"v.93=no.416-418 (1968-1969)",
"v.94=no.419-421 (1969-1970)",
"v.95=no.422-424 (1970-1971)",
"v.96=no.425-428 (1971-1972) [Incomplete]",
"v.97=no.429-432 (1972-1973) [Incomplete]",
"v.98=no.433-436 (1973-1974) [Incomplete]",
"v.99=no.437-440 (1974-1975)",
"v.100=no.441-444 (1975)",
"v.101=no.445-448 (1976-1977)",
"v.102=no.449-452 (1977-1979)",
"v.103=no.453-456 (1979)",
"v.104=no.457-460 (1980-1981)",
"v.105=no.461-464 (1981-1982)",
"v.106=no.465-468 (1982-1983)",
"v.107=no.469-472 (1983-1984)",
"v.108=[no.473-476] (1985-1986)",
"v.109=no.477-480 (1987)",
"v.110=[no.481-484] (1988-1989)",
"v.111=no.485-488 (1989)",
"v.112=no.489-492 (1990)",
"v.113=no.493-496 (1992)",
"v.114=no.497-500 (1994)",
"v.115 (1995)",
"v.116 (1996)",
"v.117 (1997)",
"v.118 (1997)",
"v.119 (1998)",
"v.120 (1998)",
"v.121 (1999)",
"v.122 (2000)",
"v.123 (2001)",
"v.124-126 (2003-2005)",
"v.125 (2004)",
"v.127-129 (2006-2008)",
"v.130 (2009)",
"v.131 (2010)",
"v.132 (2011)",
"v.133 (2011)",
"v.134 (2012)",
"v.135 (2013) [Incomplete]",
"v.136 (2014)",
"v.137 (2015)",
"v.138 (2016)",
"v.139 (2017)",
"v.140 (2018)",
"v.141 (2019)"
);

$input=array(
"v.1:no.1-4 (1977-1978)",
"v.2:no.1-4 (1979)",
"v.3:no.1-4 (1980)",
"v.4:no.1-4 (1981)",
"v.5:no.1-4 (1982)",
"v.6:no.1-4 (1983)",
"v.7:no.1-4 (1984)",
"v.8:no.1-4 (1985)",
"v.9:no.1-4 (1986)",
"v.10:no.1-4 (1987)",
"v.11:no.1-4 (1988)",
"v.12:no.1-4 (1989)",
"v.13:no.1-4;Suppl:no.1-2 (1989-1991)",
"v.14:no.1-4 (1991)",
"v.15:no.1-4;Suppl:no.3-4 (1992)",
"v.16:no.1-4 (1993)",
"v.17:no.1-4;Suppl:no.5 (1994 )",
"v.18:1-4;v.19:no.1-4 (1995-1996)",
"v.20:no.1-4 (1997)",
"v.21:no.1-4 (1998)",
"v.22:no.1-4 (1999)",
"v.23:no.1-4 (2000)",
"v.24:no.1-4 (2001)",
"v.25:no.1-4;v.26:no.1-4 (2002-2003)",
"v.27:no.1 (2004)",
"v.27:no.2-3 (2004)",
"v.27:no.4 (2004)",
"v.28:no.1 (2005)",
"v.28:no.2 (2005)",
"v.28:no.3-4 (2005)",
"v.29:no.1-2 (2006)",
"v.29:no.3-4 (2006)",
"v.30:no.1 (2007)",
"v.30:no.2 (2007)",
"v.31:no.1 (2008)",
"v.31:no.2 (2008)",
"v.32:no.1 (2009)",
"v.32:no.2 (2009)",
"v.33:no.1 (2010)",
"v.33:no.2 (2010)",
"v.34:no.1 (2011)",
"v.34:no.2 (2011)",
"v.35:no.1 (2012)",
"v.35:no.2 (2012)",
"v.36:no.1 (2013)",
"v.36:no.2 (2013)",
"v.37:no.1 (2014)",
"v.37:no.2 (2014)",
"v.38:no.1 (2015)",
"v.38:no.2 (2015)"
);


// complex

$input = array(
"v.25:no.1-4;v.26:no.1-4 (2002-2003)",
);

$input = array(
'pt.II: fasc.4',
);


	$failed = array();

	foreach ($input as $text)
	{
		$result = parse_volume($text);
		
		if ($result->parsed)
		{
			print_r($result);
		}
		else
		{
			$failed[] = $text;
		}
	}
	

	echo "Failed:\n";
	print_r($failed);
}

// make some test data
if (0)
{
	
	$input = array(
'1863',	
'pt.1-6 (1833-1838)',
'v.2:no.4 (1973:Apr.)',
"Bd.27 (1913:Mar.-1914:Jan.)",
"Bd.28 (1914)",
"Bd.36:Heft.3-4 (1922:Nov.)",
"fasc.175 (1921)",
"fasc.176-180 (1921-1922)",
"v.106:no.2 (2009:Aug.)",
"v.108-109 (2011-2012)",
"v.25:no.1-4;v.26:no.1-4 (2002-2003)",
"v.57 (1960)",
"v.57=no.239-244 (1932)",

	);
	
$input = array(
'pt.II: fasc.4',
);	

$input = array(
"v.39 (1933-36)",
);
	
	$output = array();

	foreach ($input as $text)
	{
		$result = parse_volume($text);
		
		if ($result->parsed)
		{
			unset($result->parsed);
			unset($result->text);
			unset($result->pattern);
		
			$row = array($text, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
			$output[] = json_encode($row);
		
			//print_r($result);
		}
	}
	echo "Test examples:\n";
	echo join(",\n", $output) . "\n";
	echo "\n";

}

// "proper" tests
if (0)
{
	echo "\nTesting existing patterns\n";
	echo "-------------------------\n";

	// array of arrays of pairs of input/output strings
	$testdata = '[
["1863","{\"volume\":[\"1863\"]}"],
["pt.1-6 (1833-1838)","{\"volume\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"],\"issued\":{\"date-parts\":[[1833],[1838]]}}"],
["v.2:no.4 (1973:Apr.)","{\"volume\":[\"2\"],\"issue\":[\"4\"],\"issued\":{\"date-parts\":[[1973,4]]}}"],
["Bd.27 (1913:Mar.-1914:Jan.)","{\"volume\":[\"27\"],\"issued\":{\"date-parts\":[[1913,3],[1914,1]]}}"],
["Bd.28 (1914)","{\"volume\":[\"28\"],\"issued\":{\"date-parts\":[[1914]]}}"],
["Bd.36:Heft.3-4 (1922:Nov.)","{\"volume\":[\"36\"],\"issue\":[\"3-4\"],\"issued\":{\"date-parts\":[[1922,11]]}}"],
["fasc.175 (1921)","{\"volume\":[\"175\"],\"issued\":{\"date-parts\":[[1921]]}}"],
["fasc.176-180 (1921-1922)","{\"volume\":[\"176\",\"177\",\"178\",\"179\",\"180\"],\"issued\":{\"date-parts\":[[1921],[1922]]}}"],
["v.106:no.2 (2009:Aug.)","{\"volume\":[\"106\"],\"issue\":[\"2\"],\"issued\":{\"date-parts\":[[2009,8]]}}"],
["v.108-109 (2011-2012)","{\"volume\":[\"108\",\"109\"],\"issued\":{\"date-parts\":[[2011],[2012]]}}"],
["v.25:no.1-4;v.26:no.1-4 (2002-2003)","{\"volume\":[\"25\",\"26\"],\"issued\":{\"date-parts\":[[2002],[2003]]}}"],
["v.57 (1960)","{\"volume\":[\"57\"],\"issued\":{\"date-parts\":[[1960]]}}"],
["v.57=no.239-244 (1932)","{\"volume\":[\"57\"],\"issue\":[\"239-244\"],\"issued\":{\"date-parts\":[[1932]]}}"],
["pt.II: fasc.4","{\"volume\":[2],\"issue\":[\"4\"]}"],
["v.39 (1933-36)","{\"volume\":[\"39\"],\"issued\":{\"date-parts\":[[1933],[1936]]}}"]
	]';

	$testcases = json_decode($testdata);

	//print_r($testcases);
	
	
	$failed = array();
	
	foreach ($testcases as $test)
	{
		echo "    Input: " . $test[0] . "\n";
		echo "     Test: ";
		
		$expected = $test[1];
		$output = '';
		
		$result = parse_volume($test[0]);
		
		if ($result->parsed)
		{
			unset($result->parsed);
			unset($result->text);
			unset($result->pattern);

			$output = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
		
		if (strcmp($expected, $output) == 0)
		{
			echo "ok\n";
			echo "      Got: " . $output . "\n";
		}
		else
		{
			echo "*** failed ***\n";
			echo "Expected: " . $expected . "\n";
			echo "     Got: " . $output . "\n";
			
			$failed[] = $test[0];
		}
		
		echo "\n";
	
	
	}
	echo "Failed:\n";
	print_r($failed);
	
	
}


// working space
if (0)
{
	$input=array(
	"v.1-2 (1893-1894)",
	"v.10 (1902)",
	"v.16 (1908)",
	"v.48 (1940)",
	"v.49 (1941)",
	"v.39 (1931)",
	"v.40 (1932)",
	"v.44-45 (1936-1937)",
	"v.47 (1939)",
	"v.42-43 (1934-1935)",
	"v.41;Suppl.:v.41 (1933)",
	"v.50 (1942)",
	"v.46 (1938)",
	"v.60-61 (1952-1953)",
	"v.37 (1929)",
	"v.92 (1984)",
	"v.95 (1987)",
	"v.54-55 (1946-1947)",
	"v.56-57 (1948-1949)",
	"v.38 (1930)",
	"v.62-64 (1954-1956)",
	"v.94 (1986)",
	"v.88-89 (1980-1981)",
	"v.52-53 (1944-1945)",
	"v.58-59 (1950-1951)",
	"v.93:no.2-4 (1985)",
	"v.84-85 (1976-1977)",
	"v.93:no.1 (1985)",
	"v.96 (1988)",
	"v.90-91 (1982-1983)",
	"v.33 (1925)",
	"Index v.11-50 (1893-1946)",
	"v.31 (1923)",
	"v.36 (1928)",
	"v.32 (1924)",
	"v.34 (1926)",
	"v.102 (1994)",
	"v.74-75 (1966-1967)",
	"v.65-67 (1957-1959)",
	"v.68-69 (1960-1961)",
	"v.80-81 (1972-1973)",
	"v.82-83 (1974-1975)",
	"v.51 (1943)",
	"v.99 (1991)",
	"v.97 (1989)",
	"v.98 (1990)",
	"v.70-71 (1962-1963)",
	"v.78-79 (1970-1971)",
	"v.72-73 (1964-1965)",
	"v.104-105 (1996-1997)",
	"v.106-107 (1998-1999)",
	"v.100 (1992)",
	"v.103 (1995)",
	"v.35 (1927)",
	"v.77 (1969)",
	"v.101:no.3 (1993:July)",
	"v.87 (1979)",
	"v.101:no.1 (1993:Jan.)",
	"v.86 (1978)",
	"v.101:no.4 (1993:Oct.)",
	"v.76 (1968-1969)",
	"v.101:no.2 (1993:Apr.)",
	"v.4-6(1896-1898)",
	"v.3 (1895)",
	"v.13 (1905)",
	"v.24 (1916)",
	"v.29 (1921)",
	"v.22 (1914)",
	"v.14 (1906)",
	"v.18 (1910)",
	"v.27 (1919)",
	"v.28 (1920)",
	"v.20 (1912)",
	"v.17 (1909)",
	"v.12 (1904)",
	"v.9 (1901)",
	"v.23 (1915)",
	"v.15 (1907)",
	"v.19 (1911)",
	"v.21 (1913)",
	"v.4 (1896)",
	"v.5 (1897)",
	"v.8 (1900)",
	"v.7 (1899)",
	"v.11 (1903)",
	"v.30 (1922)",
	"v.26 (1918)",
	"v.25 (1917)",
	"v.6 (1898)"
	);

	$input=array(
	"v. 23 (1916)",
	"v. 31 (1924)",
	"v. 41 (1938-39)",
	"v.36 pp.18-128 (1930)",
	"v. 13 (1906)",
	"v. 15 (1908)",
	"v. 21 (1914)",
	"v. 14 (1907)",
	"v. 16 (1909)",
	"v. 17 (1910)",
	"v. 19 (1912)",
	"v. 18 (1911)",
	"v. 22 (1915)",
	"v. 24 (1917)",
	"v. 28 (1921)",
	"v. 3 (1896)",
	"v. 4 (1897)",
	"v. 5 (1898)",
	"v. 30 (1923)",
	"v. 27 (1920)",
	"v. 32 (1925)",
	"v. 33 (1926)",
	"v. 35 (1929-30)",
	"v. 6 (1899)",
	"v. 7 (1900)",
	"v. 8 (1901)",
	"v. 9 (1902)",
	"v. 9 (Supplement, part 2) (1903)",
	"v. 40 (1936-37)",
	"v. 42 (1940-48)",
	"v. 37 (1931-32)",
	"v. 38 (1932-33)",
	"v. 36 (1930-31)",
	"v. 12 (1905)",
	"v. 34 (1927-28)",
	"v. 25 (1918)",
	"v. 1 (1894)",
	"v. 26 (1919)",
	"v. 11 (1904)",
	"v. 2 (1895)",
	"v. 10 (1903)",
	"v. 9 (Supplement, part 1) (1903)",
	"v. 20 (1913)",
	"v.39 (1933-36)",
	"v. 29 (1922)"
	);

	/*
	$input = array(
	"v.39 (1933-36)",
	);
	*/



	$input=array(
	"Bd.13 (1908-1925) [Plates]",
	"Bd.13 (1908-1925) [Text]",
	"Bd.8 (1932-1933)",
	"Bd.5 (1907-1924) [Plates]",
	"Bd.5:pt.1 (1907-1924) [Text]",
	"Bd.5:pt.2 (1907-1924) [Text]",
	"Bd.6 (1913-1935) [Plates]",
	"Bd.10 (1907-1934) [Plates]",
	"Bd.6 (1913-1935) [Text]",
	"Bd.9 (1908-1928) [Plates]",
	"Bd.9:pt.2 (1908-1928) [Text]",
	"Suppl.:Bd.2 (1933)",
	"Bd.14 (1926-1930)",
	"Suppl.:Bd.1 (1932)",
	"Bd.16 (1929-1935)",
	"Bd.9:pt.1 (1908-1928) [Text]",
	"Bd.10 (1907-1934) [Text]",
	"Suppl.:Bd.3 (1938)",
	"Suppl.:Bd.4 (1954)",
	"Bd.2 (1907-1913) [Plates]",
	"Bd.4 (1913-1916) [Text]",
	"Bd.1 (1907-1909) [Text]",
	"Bd.15 (1913-1935)",
	"Bd.3 (1907-1916) [Text]",
	"Bd.7 (1919-1931)",
	"Bd.4 (1913-1916) [Plates]",
	"Bd.3 (1907-1916) [Plates]",
	"Bd.13 (1908-1925)",
	"Bd.11 (1912-1926) [Incomplete]",
	"Bd.12 (1915-1935)"
	);

	$input = array(
	'Bd.5:pt.1 (1907-1924)',
	"Suppl.:Bd.4 (1954)",
	);

	$input=array(
	"ser.2:v.1=no.1-6 (1848)",
	"ser.2:v.2=no.7-12 (1848)",
	"ser.2:v.3=no.13-18 (1849)",
	"ser.2:v.4=no.19-24 (1849)",
	"ser.2:v.5=no.25-30 (1850)",
	"ser.2:v.6=no.31-36 (1850)",
	"ser.9:v.11=no.61-66 (1923)",
	"v.9=no.55-61 (1842)",
	"v.12=no.74-80 (1843)",
	"v.11=no.67-73 (1843)",
	"v.6=no.34-40 (1840-1841) [Lacks:Pl.VIII]",
	"v.12=no.74-80 (1843)",
	"ser.2:v.17=no.97-102 (1856)",
	"v.15=no.95-101 (1845) [Lacks:Pl.XI]",
	"ser.2:v.10=no.55-60 (1852)",
	"ser.2:v.19=no.109-114 (1857)",
	"ser.2:v.15=no.85-90 (1855)",
	"ser.2:v.16=no.91-96 (1855)",
	"ser.3:v.2=no.7-12 (1858)",
	"v.19=no.123-129 (1847)",
	"ser.3:v.6=no.31-36 (1860)",
	"v.20=no.130-136 (1847)",
	"ser.2:v.14=no.79-84 (1854)",
	"v.13=no.81-86 (1844)",
	"v.16=no.102-108 (1845)",
	"v.9=no.55-61 (1842)",
	"ser.2:v.18=no.103-108 (1856)",
	"v.7=no.41-47 (1841)",
	"ser.3:v.3=no.13-18 (1859)",
	"v.18=no.116-122 (1846)",
	"ser.2:v.8=no.43-48 (1851)",
	"ser.3:v.5=no.25-30 (1860)",
	"v.10=no.62-66 (1842)",
	"v.11=no.67-73 (1843)",
	"v.14=no.88-94 (1844-1845)",
	"v.6=no.34-40 (1840-1841)",
	"v.8=no.48-54 (1842)",
	"ser.2:v.13=no.73-78 (1854)",
	"ser.3:v.4=no.19-24 (1859)",
	"ser.3:v.1=no.1-6 (1858)",
	"ser.2:v.9=no.49-54 (1852)",
	"ser.2:v.20=no.115-121 (1857)",
	"ser.4:v.3=no.13-18 (1869)",
	"v.17=no.109-115 (1846)",
	"v.19=no.123-129 (1847)",
	"ser.7:v.18=no.103-108 (1906)",
	"ser.2:v.16=no.91-96 (1855)",
	"ser.5:v.20=no.115-120 (1887)",
	"ser.7:v.19=no.109-114 (1907)",
	"ser.8:v.13=no.73-78 (1914)",
	"ser.5:v.10=no.55-60 (1882)",
	"ser.5:v.14=no.79-84 (1884)",
	"ser.6:v.6=no.31-36 (1890)",
	"ser.7:v.17=no.97-102 (1906)",
	"ser.2:v.1=no.1-6 (1848)",
	"v.2(1868)",
	"ser.2:v.12=no.67-72 (1853)",
	"ser.2:v.11=no.61-66 (1853)",
	"ser.2:v.7=no.37-42 (1851)",
	"ser.3:v.8=no.43-48 (1861)",
	"ser.3:v.10=no.55-60 (1862)",
	"ser.3:v.18=no.103-108 (1866) [Lacks:Pl.X]",
	"ser.8:v.20=no.115-120 (1917)",
	"ser.4:v.14=no.79-84 (1874)",
	"ser.8:v.17=no.97-102 (1916)",
	"ser.8:v.15=no.85-90 (1915)",
	"ser.6:v.12=no.67-72 (1893)",
	"ser.8:v.8=no.43-48 (1911)",
	"ser.6:v.9=no.49-54 (1892)",
	"ser.8:v.10=no.55-60 (1912)",
	"ser.5:v.18=no.103-108 (1886)",
	"ser.4:v.15=no.85-90 (1875) [Lacks:Pl.IX]",
	"ser.6:v.3=no.13-18 (1889)",
	"ser.6:v.4=no.19-24 (1889)",
	"ser.2:v.4=no.19-24 (1849)",
	"ser.3:v.14=no.79-84 (1864)",
	"ser.3:v.20=no.115-120 (1867)",
	"ser.5:v.14=no.79-84 (1884)",
	"ser.9:v.5=no.25-30 (1920) [Lacks:Pl.IX]",
	"ser.6:v.14=no.79-84 (1894)",
	"ser.9:v.7=no.37-42 (1921)",
	"ser.8:v.19=no.109-114 (1917)",
	"ser.7:v.1=no.1-6 (1898)",
	"ser.6:v.18=no.103-108 (1896)",
	"ser.8:v.6=no.31-36 (1910)",
	"ser.7:v.6=no.31-36 (1900)",
	"ser.2:v.5=no.25-30 (1850)",
	"ser.9:v.2=no.7-12 (1918)",
	"ser.3:v.11=no.61-66 (1863)",
	"ser.9:v.4=no.19-24 (1919)",
	"ser.2:v.1=no.1-6 (1848)",
	"ser.4:v.20=no.115-120 (1877)",
	"ser.6:v.8=no.43-48 (1891)",
	"ser.9:v.6=no.31-36 (1920)",
	"ser.9:v.3=no.13-18 (1919)",
	"ser.5:v.17=no.97-102 (1886)",
	"ser.9:v.9=no.49-54 (1922)",
	"ser.8:v.7=no.37-42 (1911)",
	"ser.8:v.13=no.73-78 (1914)",
	"ser.5:v.15=no.85-90 (1885)",
	"ser.4:v.18=no.103-108 (1876)",
	"ser.6:v.15=no.85-90 (1895)",
	"ser.9:v.1=no.1-6 (1918)",
	"ser.3:v.16=no.91-96 (1865)",
	"ser.8:v.16=no.91-96 (1915)",
	"ser.3:v.15=no.85-90 (1865)",
	"ser.4:v.12=no.67-72 (1873)",
	"ser.5:v.12=no.67-72 (1883)",
	"ser.6:v.17=no.97-102 (1896)",
	"ser.4:v.17=no.97-102 (1876)",
	"ser.7:v.3=no.13-18 (1899)",
	"ser.3:v.17=no.97-102 (1866)",
	"ser.2:v.3=no.13-18 (1849)",
	"ser.4:v.9=no.49-54 (1872)",
	"ser.2:v.6=no.31-36 (1850)",
	"ser.3:v.7=no.37-42 (1861)",
	"ser.8:v.5=no.25-30 (1910)",
	"ser.4:v.2=no.7-12 (1868)",
	"ser.7:v.13=no.73-78 (1904)",
	"ser.7:v.5=no.25-30 (1900)",
	"ser.5:v.19=no.109-114 (1887)",
	"ser.5:v.5=no.25-30 (1880)",
	"ser.7:v.2=no.7-12 (1898)",
	"ser.4:v.5=no.25-30 (1870)",
	"ser.8:v.20=no.115-120 (1917)",
	"ser.9:v.4=no.19-24 (1919)",
	"ser.8:v.14=no.79-84 (1914)",
	"ser.8:v.17=no.97-102 (1916) [Lacks:Pl.VI]",
	"ser.7:v.13=no.73-78 (1904)",
	"ser.8:v.6=no.31-36 (1910)",
	"ser.2:v.15=no.85-90 (1855)",
	"ser.8:v.11=no.61-66 (1913) [Lacks:Pl.VII]",
	"ser.2:v.20=no.115-121 (1857)",
	"ser.9:v.5=no.25-30 (1920)",
	"ser.9:v.3=no.13-18 (1919)",
	"ser.3:v.4=no.19-24 (1859)",
	"ser.8:v.9=no.49-54 (1912)",
	"ser.2:v.19=no.109-114 (1857) [Lacks:Pl.XI]",
	"ser.8:v.10=no.55-60 (1912) [Lacks:Pl.XII]",
	"ser.8:v.13=no.73-78 (1914)",
	"ser.9:v.1=no.1-6 (1918)",
	"v.11=no.67-73 (1843) [Lacks:Pl.X]",
	"ser.3:v.5=no.25-30 (1860)",
	"ser.3:v.2=no.7-12 (1858)",
	"ser.2:v.14=no.79-84 (1854)",
	"ser.3:v.1=no.1-6 (1858)",
	"ser.2:v.16=no.91-96 (1855)",
	"ser.6:v.17=no.97-102 (1896) [Lacks:pg.114-115]",
	"ser.4:v.3=no.13-18 (1869)",
	"ser.4:v.4=no.19-24 (1869)",
	"v.15=no.95-101 (1845)",
	"v.9=no.55-61 (1842)",
	"v.8=no.48-54 (1842)",
	"ser.2:v.18=no.103-108 (1856)",
	"ser.2:v.2=no.7-12 (1848)",
	"ser.2:v.4=no.19-24 (1849)",
	"ser.2:v.5=no.25-30 (1850)",
	"ser.6:v.6=no.31-36 (1890) [Incomplete]",
	"ser.7:v.4=no.19-24 (1899)",
	"ser.4:v.14=no.79-84 (1874)",
	"ser.7:v.3=no.13-18 (1899)",
	"ser.6:v.18=no.103-108 (1896)",
	"ser.6:v.20=no.115-120 (1897)",
	"ser.4:v.12=no.67-72 (1873)",
	"ser.7:v.16=no.91-96 (1905)",
	"ser.9:v.6=no.31-36 (1920) [Lacks:Pl.XVI]",
	"ser.4:v.19=no.109-114 (1877)",
	"ser.6:v.13=no.73-78 (1894)",
	"ser.6:v.7=no.37-42 (1891)",
	"ser.7:v.15=no.85-90 (1905)",
	"ser.4:v.8=no.43-48 (1871) [Lacks:Pl.VII]",
	"ser.6:v.8=no.43-48 (1891)",
	"ser.7:v.17=no.97-102 (1906)",
	"ser.6:v.15=no.85-90 (1895)",
	"ser.7:v.6=no.31-36 (1900)",
	"ser.4:v.5=no.25-30 (1870)",
	"ser.4:v.17=no.97-102 (1876)",
	"ser.6:v.19=no.109-114 (1897)",
	"ser.6:v.9=no.49-54 (1892) [Lacks:Pl.VIII]",
	"ser.4:v.20=no.115-120 (1877) [Lacks:Pl.II]",
	"ser.6:v.4=no.19-24 (1889)",
	"ser.7:v.9=no.49-54 (1902)",
	"ser.9:v.8=no.43-48 (1921)",
	"ser.7:v.2=no.7-12 (1898)",
	"ser.7:v.10=no.55-60 (1902)",
	"ser.7:v.1=no.1-6 (1898)",
	"ser.7:v.18=no.103-108 (1906)",
	"ser.7:v.11=no.61-66 (1903)",
	"ser.3:v.7=no.37-42 (1861)",
	"ser.2:v.3=no.13-18 (1849)",
	"v.7=no.41-47 (1841)",
	"v.13=no.81-86 (1844)",
	"v.14=no.88-94 (1844-1845)",
	"v.16=no.102-108 (1845)",
	"ser.2:v.17=no.97-102 (1856)",
	"ser.3:v.3=no.13-18 (1859)",
	"ser.8:v.2=no.7-12 (1908)",
	"ser.8:v.3=no.13-18 (1909)",
	"ser.8:v.4=no.19-24 (1909)",
	"ser.8:v.7=no.37-42 (1911)",
	"ser.8:v.8=no.43-48 (1911)",
	"ser.8:v.12=no.67-72 (1913)",
	"v.12=no.74-80 (1843) [Lacks:Pl.III]",
	"ser.9:v.2=no.7-12 (1918)",
	"ser.8:v.18=no.103-108 (1916) [Lacks:Pl.III]",
	"ser.8:v.19=no.109-114 (1917) [Lacks:Pl.XIII]",
	"ser.8:v.16=no.91-96 (1915)",
	"ser.2:v.9=no.49-54 (1852)",
	"v.17=no.109-115 (1846)",
	"ser.3:v.10=no.55-60 (1862)",
	"v.6=no.34-40 (1840-1841) [Lacks:Plate]",
	"ser.2:v.12=no.67-72 (1853)",
	"ser.3:v.8=no.43-48 (1861)",
	"ser.3:v.19=no.109-114 (1867)",
	"ser.2:v.13=no.73-78 (1854)",
	"ser.2:v.1=no.1-6 (1848)",
	"ser.3:v.13=no.73-78 (1864) [Lacks:Pl.XVII]",
	"ser.3:v.6=no.31-36 (1860)",
	"ser.3:v.9=no.49-54 (1862) [Lacks:Pl.VIII]",
	"ser.2:v.6=no.31-36 (1850)",
	"ser.3:v.11=no.61-66 (1863)",
	"ser.2:v.10=no.55-60 (1852)",
	"ser.3:v.20=no.115-120 (1867)",
	"ser.4:v.2=no.7-12 (1868) [Lacks:Pl.VI]",
	"ser.4:v.1=no.1-6 (1868) [Lacks:Pl.VIII]",
	"ser.3:v.16=no.91-96 (1865)",
	"ser.3:v.14=no.79-84 (1864)",
	"ser.2:v.8=no.43-48 (1851)",
	"ser.3:v.15=no.85-90 (1865)",
	"ser.3:v.18=no.103-108 (1866)",
	"ser.9:v.10=no.55-60 (1922)",
	"ser.7:v.20=no.115-120 (1907)",
	"ser.7:v.19=no.109-114 (1907)",
	"ser.7:v.14=no.79-84 (1904)",
	"ser.8:v.15=no.85-90 (1915)",
	"ser.4:v.11=no.61-66 (1873) [Lacks:pg.241-320]",
	"ser.4:v.18=no.103-108 (1876)",
	"ser.8:v.1=no.1-6 (1908)",
	"ser.4:v.15=no.85-90 (1875)",
	"ser.4:v.7=no.37-42 (1871)",
	"ser.9:v.9=no.49-54 (1922)",
	"ser.7:v.5=no.25-30 (1900)",
	"ser.4:v.13=no.73-78 (1874)",
	"ser.6:v.14=no.79-84 (1894)",
	"ser.6:v.12=no.67-72 (1893) [Lacks:Pl.XIII]",
	"ser.6:v.11=no.61-66 (1893)",
	"ser.7:v.7=no.37-42 (1901)",
	"ser.5:v.7=no.37-42 (1881)",
	"ser.4:v.3=no.13-18 (1869)",
	"ser.4:v.7=no.37-42 (1871)",
	"ser.4:v.11=no.61-66 (1873)",
	"ser.6:v.16=no.91-96 (1895) [Incomplete]",
	"ser.6:v.2=no.7-12 (1888)",
	"ser.7:v.4=no.19-24 (1899)",
	"ser.4:v.16=no.91-96 (1875)",
	"ser.5:v.3=no.13-18 (1879)",
	"ser.5:v.4=no.19-24 (1879)",
	"ser.6:v.7=no.37-42 (1891)",
	"ser.5:v.6=no.31-36 (1880)",
	"ser.4:v.10=no.55-60 (1872)",
	"ser.5:v.2=no.7-12 (1878)",
	"ser.5:v.9=no.49-54 (1882)",
	"ser.6:v.1=no.1-6 (1888)",
	"ser.7:v.7=no.37-42 (1901) [Lacks:pg.389-390]",
	"ser.9:v.8=no.43-48 (1921)",
	"ser.7:v.20=no.115-120 (1907) [Lacks:Pl.XVIII]",
	"ser.4:v.19=no.109-114 (1877)",
	"ser.3:v.13=no.73-78 (1864)",
	"ser.8:v.2=no.7-12 (1908)",
	"ser.4:v.8=no.43-48 (1871)",
	"ser.3:v.9=no.49-54 (1862)",
	"ser.6:v.20=no.115-120 (1897)",
	"ser.6:v.5=no.25-30 (1890)",
	"ser.5:v.13=no.73-78 (1884)",
	"ser.6:v.10=no.55-60 (1892)",
	"ser.6:v.6=no.31-36 (1890)",
	"ser.3:v.12=no.67-72 (1863)",
	"ser.7:v.14=no.79-84 (1904)",
	"ser.6:v.11=no.61-66 (1893)",
	"ser.4:v.4=no.19-24 (1869)",
	"ser.5:v.1=no.1-6 (1878)",
	"ser.4:v.1=no.1-6 (1868)",
	"ser.5:v.11=no.61-66 (1883)",
	"ser.4:v.6=no.31-36 (1870)",
	"ser.5:v.10=no.55-60 (1882) [Lacks:Pl.XVIII]",
	"ser.7:v.8=no.43-48 (1901)",
	"ser.7:v.12=no.67-72 (1903)",
	"ser.5:v.16=no.91-96 (1885)",
	"ser.5:v.20=no.115-120 (1887)",
	"ser.5:v.8=no.43-48 (1881)"
	);

	/*
	$input = array(
	"ser.5:v.11=no.61-66 (1883)",
	//"v.6=no.34-40 (1840-1841) [Lacks:Plate]",
	);
	*/

	$input=array(
	"v.14 (1915-1924)",
	"v.98:pt.1-6 (1987-1988)",
	"v.105 (1997-1999)",
	"v.26 (1928)",
	"v.20 (1924-1926)",
	"v.104:pt.9 (1995)",
	"v.104:pt.12 (1996)",
	"v.104:pt.10 (1996)",
	"v.25 (1927-1929)",
	"v.96:pt.6-9 (1986-1988)",
	"v.21:pt.1 (1925)",
	"v.96:pt.1-5 (1985)",
	"v.84 (1981)",
	"v.103:pt.4 (1993)",
	"v.77:pt.3 (1978)",
	"v.104:pt.11 (1996)",
	"v.88 (1982)",
	"v.19 (1923-1925)",
	"Table of Contents v.77 (1978-1979)",
	"v.77:pt.2 (1978)",
	"v.89:pt.5 (1982)",
	"v.89:pt.2 (1982)",
	"v.89:pt.4 (1982)",
	"v.104:pt.2 (1994)",
	"v.104:pt.4 (1994)",
	"v.103:pt.3 (1993)",
	"v.104:pt.1 (1994)",
	"v.104:pt.3 (1994)",
	"v.77:pt.7 (1979)",
	"v.103:pt.2 (1993)",
	"v.77:pt.5 (1978)",
	"v.77:pt.4 (1978)",
	"v.77:pt.8 (1979)",
	"v.89:pt.3 (1982)",
	"v.77:pt.6 (1979)",
	"v.104:pt.5 (1994)",
	"v.103:pt.5 (1993)",
	"v.77:pt.10 (1979)",
	"v.104:pt.7 (1995)",
	"v.103:pt.7 (1994)",
	"v.94 (1983-1984)",
	"v.103:pt.1 (1993)",
	"v.95 (1985)",
	"v.101 (1991-1992)",
	"v.22 (1925-1928)",
	"v.99:pt.1-6 (1990)",
	"v.91 (1983)",
	"v.92 (1983-1984)",
	"v.107 (2001)",
	"v.24 (1929-1938)",
	"v.75 (1978)",
	"v.78 (1979)",
	"v.80 (1980)",
	"v.104:pt.8 (1995)",
	"v.97 (1985-1988)",
	"v.85 (1981-1982)",
	"v.93 (1984)",
	"v.89:pt.1 (1982)",
	"v.104:pt.6 (1994)",
	"v.103:pt.6 (1993)",
	"v.76 (1978)",
	"v.79 (1979-1980)",
	"v.69:pt.6 (1976)",
	"v.69:pt.3 (1975)",
	"v.69:pt.7 (1976)",
	"v.69:pt.2 (1975)",
	"v.48:pt.12 (1965)",
	"v.48:pt.2 (1964)",
	"v.48:pt.6 (1964)",
	"v.48:pt.7 (1965)",
	"v.48:pt.4 (1964)",
	"v.48:pt.21 (1967)",
	"v.48:pt.11 (1965)",
	"v.48:pt.19 (1966)",
	"v.48:pt.20 (1966)",
	"v.48:pt.8 (1965)",
	"v.56:pt.2 (1970)",
	"v.56:pt.1 (1969)",
	"v.72:pt.8 (1977)",
	"v.72:pt.6 (1977)",
	"v.72:pt.7 (1977)",
	"Table of Contents v.72 (1976-1977)",
	"v.72:pt.10 (1977)",
	"v.72:pt.12 (1977)",
	"v.72:pt.9 (1977)",
	"v.72:pt.11 (1977)",
	"v.72:pt.13 (1977)",
	"v.51 (1968)",
	"v.53:pt.1 (1968)",
	"v.54 (1969)",
	"v.69:pt.4 (1975)",
	"v.48:pt.5 (1964)",
	"v.48:pt.22 (1967)",
	"v.48:pt.10 (1965)",
	"v.48:pt.15 (1965)",
	"v.48:pt.14 (1965)",
	"v.69:pt.1 (1975)",
	"v.48:pt.13 (1965)",
	"v.56:pt.3 (1970)",
	"v.48:pt.1 (1964)",
	"v.48:pt.17 (1965)",
	"v.56:pt.4 (1970)",
	"v.70:pt.4 (1989)",
	"v.72:pt.1 (1976)",
	"v.56:pt.6 (1971)",
	"v.70:pt.3 (1981)",
	"v.48:pt.18 (1966)",
	"v.70:pt.1 (1976)",
	"v.56:pt.5 (1971)",
	"v.69:pt.8 (1976)",
	"v.72:pt.2 (1976)",
	"v.70:pt.2 (1976)",
	"v.72:pt.5 (1977)",
	"v.69:pt.9 (1976)",
	"v.72:pt.3 (1976)",
	"v.69:pt.10 (1976)",
	"v.69:pt.11 (1976)",
	"Table of Contents v.69 (1975-1976)",
	"v.53:pt.2 (1968)",
	"v.72:pt.4 (1976)",
	"v.99:pt.7-12 (1990-1991)",
	"v.48:pt.16 (1965)",
	"v.102 (1992-1993)",
	"v.81 (1980)",
	"v.82 (1980)",
	"v.83 (1980-1981)",
	"v.90 (1982-1983)",
	"v.28 (1929-1932)",
	"v.35 (1956)",
	"v.34 (1938)",
	"v.38 (1950)",
	"v.47;Suppl.:v.45 (1963-1974)",
	"v.86 (1981-1982)",
	"v.30;Index v.1-30 (1931-1934)",
	"v.44;Suppl.:v.44 (1957-1959)",
	"v.37 (1947-1952)",
	"v.43 (1955-1957)",
	"v.40 (1952-1956)",
	"v.41 (1952-1955)",
	"v.39 (1952)",
	"v.33 (1939)",
	"v.45 (1959-1960)",
	"v.46 (1961-1963)",
	"v.27 (1929)",
	"v.36 (1942-1947)",
	"v.42 (1953-1956)",
	"v.31 (1934-1950)",
	"v.32 (1935-1940)",
	"v.29 (1929-1931)",
	"v.108-109 (2001-2003)",
	"v.61-62 (1973-1974)",
	"v.50 (1966-1968)",
	"v.66 (1974-1975)",
	"v.55 (1969-1970)",
	"v.57 (1970-1971)",
	"v.64 (1974)",
	"v.58:pt.4 (1988)",
	"v.73 (1977)",
	"v.68 (1975)",
	"v.67 (1975)",
	"v.65 (1974)",
	"v.71 (1976)",
	"v.110-112 (2003-2004)",
	"v.21:pt.2 (1927)",
	"v.63 (1974)",
	"v.49 (1967-1968)",
	"v.59 (1971-1972)",
	"v.60 (1972-1973)",
	"v.58:pt.1-3 (1972-1981)",
	"v.52 (1968-1969)",
	"v.69 (1975-1976)",
	"v.100 (1991-1992)",
	"v.48 (1964-1965)",
	"v.74 (1977-1978)",
	"v.77 (1978-1979)",
	"v.85 (1981-1982)",
	"v.23 (1925-1926)",
	"v.16 (1917-1933)",
	"v.3 (1903-1905)",
	"v.6 (1908-1911)",
	"v.12 (1913-1924)",
	"v.1 (1898-1899)",
	"v.17 (1917-1920)",
	"v.11 (1911-1917)",
	"v.18 (1921)",
	"v.13 (1913-1923)",
	"v.7 (1908-1913)",
	"v.15 (1914-1916)",
	"v.10 (1911-1914)",
	"v.106 (2003)",
	"v.87 (1982)",
	"v.98:pt.7-11 (1989)",
	"v.9 (1911-1918)",
	"v.4 (1903-1908)",
	"v.8 (1911)",
	"v.5 (1906-1910)",
	"v.2 (1900-1902)"
	);

	$input=array(
	"v.2(1980)",
	);

	/*
	$input=array(
	"t.59-61 (1919-1922)",
	"t.58 (1914)",
	);
	*/

	$input=array(
	"v.1=no.1-4 (1911-1913)",
	"ser.7:v.20=no.115-120 (1907) [Lacks:Pl.XVIII]",
	"v.99:pt.7-12 (1990-1991)",
	);

	$failed = array();

	foreach ($input as $text)
	{
		$result = parse_volume($text);
	
		if ($result->parsed)
		{
			print_r($result);
		}
		else
		{
			$failed[] = $text;
		}
	}


	echo "Failed:\n";
	print_r($failed);
}

if (0)
{
	$input=array(
	"Insecta. Coleoptera. v. 3. pt. 1. Serricornia: Buprestidae by C.O. Waterhouse; Throscidae and Eucnom",
	);
	
	$input=array(	
	'no.71 (2019)',
	);

	$input=array(	
	'ser.2:d.7 (1901-1902)',
	);
	
	$input=array(	
	'Vol. 5 : no. 1-no. 2 (1986)',
	);

	$failed = array();

	foreach ($input as $text)
	{
		$result = parse_volume($text);
	
		if ($result->parsed)
		{
			print_r($result);
		}
		else
		{
			$failed[] = $text;
		}
	}


	echo "Failed:\n";
	print_r($failed);

}


?>
