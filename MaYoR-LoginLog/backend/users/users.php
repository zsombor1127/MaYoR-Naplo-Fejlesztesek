
//Ezt a www/include/modules/naplo/share/diak.php fájlba kell bemásolni
########################################################################################
//Id alapján visszaadja a diák felhasználónevét
function getDiakFelhasznaloById($uId) {
	$studyId = getDiakAdatById($uId)['oId'];
		
	$lr = db_connect("private auth");
	$q = "SELECT userAccount FROM accounts WHERE studyId = '%s'"; 
	$userAccount = db_query($q, array('lr'=>$lr,'fv' => 'getDiakFelhasznaloById', 'modul' => "private auth", 'result' => 'indexed', 'values' => array($studyId)));
	if(count($userAccount[0]) == 0 || !is_array($userAccount[0])) {
		return False;
	} else {
		return $userAccount[0]['userAccount'];
	}
}
########################################################################################

//Ezt a www/include/modules/naplo/share/tanar.php fájlba kell bemásolni
########################################################################################
//Id alapján visszaadja a tanár felhasználónevét
function getTanarFelhasznaloById($uId) {
	$studyId = getTanarAdatById($uId)['oId'];
	
	$lr = db_connect("private auth");
	$q = "SELECT userAccount FROM accounts WHERE studyId = '%s'"; 
	$userAccount = db_query($q, array('lr'=>$lr,'fv' => 'getTanarFelhasznaloById', 'modul' => "private auth", 'result' => 'indexed', 'values' => array($studyId)));
	if(count($userAccount[0]) == 0 || !is_array($userAccount[0])) {
		return False;
	} else {
		return $userAccount[0]['userAccount'];
	}
}
########################################################################################
