# iana-enterprise-numbers-fetcher
Fetch the PEN List from IANA and optionally search in it.

### Example
````PHP
header('Content-Type: text/plain');
$Fetcher = new IanaPenListFetcher();
$result = $Fetcher();
 echo print_r(count($result), true). " Records found\n";
 
//This should search and find the SAME entry (by email, id, oid, name, org):
 echo print_r($Fetcher('till@webfan.de'), true). "\n";
 echo print_r($Fetcher(37553), true). "\n";
 echo print_r($Fetcher('1.3.6.1.4.1.37553'), true). "\n";
 echo print_r($Fetcher('Wehowski'), true). "\n";
 echo print_r($Fetcher('frdl'), true). "\n";
 ````
 **Optionally with custom configuration:**
````PHP 
$Fetcher->setRoot('1.3.6.1.4.1');
$Fetcher->setUrl('https://www.iana.org/assignments/enterprise-numbers/enterprise-numbers');
$Fetcher->setCachelimit(60 * 60);
$Fetcher->setCachefile(__DIR__.\DIRECTORY_SEPARATOR.'penlist.php');
````
