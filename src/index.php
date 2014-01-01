<?

if (!@include 'VerySimpleProxy.class.php') {
    die('Could not load proxy');
}

$proxy = new VerySimpleProxy('http://chitanka.info/');

?>