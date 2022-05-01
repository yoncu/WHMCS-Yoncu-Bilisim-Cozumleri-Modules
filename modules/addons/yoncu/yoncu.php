<?php
function yoncu_config(){
	return array(
		'name' => 'Yöncü Bilişim Çözümleri',
		'description' => 'yoncu.com sistemleri için genel modül yapılandırmasıdır. Özel talepler baz alınarak ücretli olarak geliştirilerek genele uygulanır. Modül özelliklerinin geliştirilmesi öneri ve talepleriniz oneri@yoncu.com mail adresi ile iletişime geçmelisiniz.', // Description displayed within the admin interface
		'author' => '<a href="https://www.yoncu.com/" target="_blank">Osbil Technology Ltd.</a>',
		'language' => 'turkish',
		'version' => 1.1,
		'premium' => true,
		'fields' => array(
			'YoncuApiID' => array(
				'FriendlyName' => 'Yöncü API ID',
				'Type' => 'text',
				'Size' => '11',
				'Default' => '0',
				'Description' => '<br/>Bu Bilgiye "yoncu.com / Üye İşlemleri / Menü Devamı / Güvenlik Ayarları / API Erişim" Menüsünden Ulaşabilirsiniz',
			),
			'YoncuApiKey' => array(
				'FriendlyName' => 'Yöncü API Key',
				'Type' => 'password',
				'Size' => '40',
				'Default' => $_SERVER['HTTP_HOST'],
				'Description' => '<br/>Bu Bilgiye "yoncu.com / Üye İşlemleri / Menü Devamı / Güvenlik Ayarları / API Erişim" Menüsünden Ulaşabilirsiniz',
			),
			'YoncuTest' => array(
				'FriendlyName' => 'Test Modu',
				'Type' => 'yesno',
				'Description' => 'İşaretlenir ise bu modül üzerinde yapılan işlemlerde işlem başarılı cevabı alınır fakat işlem gerçekleştirilmez.',
			),
			'KullaniciTuru' => array(
				'FriendlyName' => 'Kullanıcı Türü',
				'Type' => 'dropdown',
				'Options' => array(
					'1' => 'Şahıs',
					'2' => 'Bireysel Firma',
					'3' => 'Şirket',
				),
				'Default' => '1',
				'Description' => 'Lütfen resmi işleyiş türünü seçiniz, resmi olarak firmanız yok ise şahıs seçmelisiniz.',
			),
			'DebugMode' => array(
				'FriendlyName' => 'Hata Ayıklama ve Bildirim Modu',
				'Type' => 'radio',
				'Options' => 'Kapalı,Açık(Mail İle Bildir),Açık(Log Olarak Yaz)',
				'Description' => 'Modül ile ilgili bir hata oluşturunda yapılacak işlem.',
				'Default' => 'Kapalı',
			),
			'SiteTanitim' => array(
				'FriendlyName' => 'Sitenizi Tanıtın',
				'Type' => 'textarea',
				'Rows' => '8',
				'Cols' => '100',
				'Default' => "Firma Adı:\nSite Adresi:\nYönetici Cep Telefonu:\nHost Firması:\nAçık Adresi:\nAdmin URL:\nAdmin Kullanıcı adı ve şifresi:\nDiğer:",
				'Description' => 'Bu alanı olabildiğince doğru ve eksiksiz doldurunuz, eğer Yöncü api ile ilgili bir hata, illegal kullanım veya izinsiz erişim gibi bir durum tespit eder ise bu bilgiler ile erişim ve kontrol sağlar. Bu bilgilerden biri hatalı ise erişilemez ise Yöncü üyelik ve hizmetleri suspend edilebilir.',
			),
		),
	);
}
function yoncu_logModuleCall($Is,$Req,$Res){
    logModuleCall('yoncu',$Is,var_export($Req,true),null,var_export($Res,true),array());
}
function yoncu_curl($Hizmet,$Islem,$params,$PostVeri=array(),$Deneme=0){
	$PostVeri['id']	= @mysql_fetch_assoc(@mysql_query("select ayar_ici from mod_yoncu_ayar where ayar_adi = 'YoncuApiID'"))['YoncuApiID'];
	if(empty($PostVeri['id'])){unset($PostVeri['id']);}
	$PostVeri['key']= @mysql_fetch_assoc(@mysql_query("select ayar_ici from mod_yoncu_ayar where ayar_adi = 'YoncuApiKey'"))['YoncuApiKey'];
	if(empty($PostVeri['key'])){unset($PostVeri['key']);}
	$URL	= 'https://www.yoncu.com/apiler/'.$Hizmet.'/'.$Islem.'.php';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir().DIRECTORY_SEPARATOR.'yoncu.com');
	curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir().DIRECTORY_SEPARATOR.'yoncu.com');
	curl_setopt($ch, CURLOPT_USERAGENT, 'WHMCS Modules '.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	curl_setopt($ch, CURLOPT_REFERER, $URL);
	curl_setopt($ch, CURLOPT_URL,'https://www.yoncu.com/YoncuTest/YoncuSec_Token');
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: YoncuKoruma='.$_SERVER['SERVER_ADDR'].';YoncuKorumaRisk=0;']);
	$Token = trim(curl_exec($ch));
	if(strlen($Token) != 32){
		return array(false,'Token Alınamadı');
	}
	curl_setopt($ch, CURLOPT_URL,$URL);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: YoncuKoruma='.$_SERVER['SERVER_ADDR'].';YoncuKorumaRisk=0;YoncuSec-v1='.$Token]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($PostVeri));
	$Json = curl_exec($ch);
	$HttpStatus	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
    yoncu_logModuleCall('curl',array($_REQUEST,$Hizmet,$Islem,$params,$PostVeri,$Deneme),$Json);
	if($HttpStatus != 200){
		if($Deneme < 4){
			sleep(3);
			return yoncu_curl($Hizmet,$Islem,$params,$PostVeri,($Deneme+1));
		}
		return array(false,'Veri Çekilemedi. Status: '.$HttpStatus);
	}elseif(trim($Json) != ""){
		return json_decode($Json);
	}else{
		return array(false,'Veri Boş Geldi');
	}
	curl_close($ch);
}
function yoncu_activate(){
    $query = <<<MySql
		CREATE TABLE IF NOT EXISTS `mod_yoncu_ayar` (
			`ayar_adi` varchar(255) NOT NULL,
			`ayar_ici` text NOT NULL,
			PRIMARY KEY (`ayar_adi`)
		)
		ENGINE=MyISAM  DEFAULT CHARSET=utf8;
		insert into `mod_yoncu_ayar` values ('YoncuApiID',null),('YoncuApiKey',null);
MySql;
    full_query($query);
    return array(
        'status' => 'success',
        'description' => 'Modül aktif edildi, eklentiler menüsünden modül adına tıklayarak yönetebilirsiniz.',
    );
}
function yoncu_deactivate(){
    $query = "DROP TABLE `mod_yoncu_ayar`";
    full_query($query);
    yoncu_logModuleCall('deactivate',$_REQUEST,$query);
    return array(
        'status' => 'success',
        'description' => 'Modül kaldırıldı ve modül ayarları silindi. Artık eklentiler menüüsnde bu modül yer almayacaktır.',
    );
}
function yoncu_upgrade($vars){
	return true;
}
function yoncu_output($vars){
    yoncu_logModuleCall('output',$_REQUEST,$vars);
	list($Durum,$Bilgi)	= yoncu_curl('WHMCS','EklentiBilgi',$vars);
	if(!$Durum){
		echo '<h2 style="color:red;width:100%;background-color:#ffe6e6;border:1px solid red;padding:10px;">Hata: '.$Bilgi.'</h2><br/>';
	}
    if(is_file("../modules/registrars/yoncu/yoncu.php")){
    	$DomainModul	= true;
    	if(isset($_REQUEST['DomainModul']) and $_REQUEST['DomainModul'] == 'kaldir'){
    		if(unlink("../modules/registrars/yoncu/yoncu.php")){
	    		rmdir("../modules/registrars/yoncu/");
    			$DomainModul	= false;
    		}
    	}else{
	    	Goto DomainModulOnar;
    	}
    }else{
    	$DomainModul	= false;
    	if(isset($_REQUEST['DomainModul']) and $_REQUEST['DomainModul'] == 'yukle'){
    		DomainModulOnar:
			if($Durum and isset($Bilgi->DomainModul->Version) and isset($Bilgi->DomainModul->KaynakKod)){
				mkdir("../modules/registrars/yoncu/");
				chmod("../modules/registrars/yoncu/", 0777);
				if(file_put_contents("../modules/registrars/yoncu/yoncu.php",$Bilgi->DomainModul->KaynakKod)){
					chmod("../modules/registrars/yoncu/yoncu.php", 0777);
					$DomainModul	= true;
				}
			}
    	}
    }
    if(is_file("../modules/servers/yoncu/yoncu.php")){
    	$ServerModul	= true;
    	if(isset($_REQUEST['ServerModul']) and $_REQUEST['ServerModul'] == 'kaldir'){
    		if(unlink("../modules/servers/yoncu/yoncu.php")){
    			rmdir("../modules/servers/yoncu/");
    			$ServerModul	= false;
    		}
    	}else{
	    	Goto ServerModulOnar;
    	}
    }else{
    	$ServerModul	= false;
    	if(isset($_REQUEST['ServerModul']) and $_REQUEST['ServerModul'] == 'yukle'){
    		ServerModulOnar:
			if($Durum and isset($Bilgi->ServerModul->Version) and isset($Bilgi->ServerModul->KaynakKod)){
				mkdir("../modules/servers/yoncu/");
				chmod("../modules/servers/yoncu/", 0777);
				if(file_put_contents("../modules/servers/yoncu/yoncu.php",$Bilgi->ServerModul->KaynakKod)){
					chmod("../modules/servers/yoncu/yoncu.php", 0777);
					$ServerModul	= true;
				}
			}
    	}
    }
    if(is_file("../modules/notifications/YoncuWhatsApp/YoncuWhatsApp.php")){
    	$YoncuWhatsAppModul	= true;
    	if(isset($_REQUEST['YoncuWhatsAppModul']) and $_REQUEST['YoncuWhatsAppModul'] == 'kaldir'){
    		unlink("../modules/notifications/YoncuWhatsApp/logo.png");
    		if(unlink("../modules/notifications/YoncuWhatsApp/YoncuWhatsApp.php")){
    			rmdir("../modules/notifications/YoncuWhatsApp/");
    			$YoncuWhatsAppModul	= false;
    		}
    	}else{
	    	Goto YoncuWhatsAppOnar;
    	}
    }else{
    	$YoncuWhatsAppModul	= false;
    	if(isset($_REQUEST['YoncuWhatsAppModul']) and $_REQUEST['YoncuWhatsAppModul'] == 'yukle'){
    		YoncuWhatsAppOnar:
			if($Durum and isset($Bilgi->YoncuWhatsAppModul->Version) and isset($Bilgi->YoncuWhatsAppModul->KaynakKod)){
				mkdir("../modules/notifications/YoncuWhatsApp/");
				chmod("../modules/notifications/YoncuWhatsApp/", 0777);
				if(file_put_contents("../modules/notifications/YoncuWhatsApp/YoncuWhatsApp.php",$Bilgi->YoncuWhatsAppModul->KaynakKod)){
					chmod("../modules/notifications/YoncuWhatsApp/YoncuWhatsApp.php", 0777);
					$YoncuWhatsAppModul	= true;
				}
			}
    	}
    }
	if(isset($_REQUEST['Yoncu']) and $_REQUEST['Yoncu'] == 'yenidenyukle'){
		if($Durum and isset($Bilgi->Yoncu->Version) and isset($Bilgi->Yoncu->KaynakKod)){
			mkdir("../modules/addons/yoncu/");
			chmod("../modules/addons/yoncu/", 0777);
			if(!file_put_contents("../modules/addons/yoncu/yoncu.php",$Bilgi->Yoncu->KaynakKod)){
				chmod("../modules/addons/yoncu/yoncu.php", 0777);
				echo "<h2 style=color:red>/modules/addons/yoncu/yoncu.php İçeriği Yüklenemedi</h2>";
			}
		}
	}
    echo '
<img src="//www.yoncu.com/resimler/genel/logo.png"/>
<hr/>
<p>Bu eklenti Yöncü sistemlerini sitenize entegre etmek için altyapıyı kurar ve eklentileri takip, yükleme, kaldırma, güncelleme, ayarlama için size yardımcı olur.</p>
<div class="inset-grey-bg">
    <div class="inset-element-container">
	    <div class="row">
	        <div class="col-sm-3 bottom-xs-margin">
	            <b>Yöncü Eklentisi</b>
	        </div>
	        <div class="col-sm-6 bottom-xs-margin">
	        	Bu bulunduğunuz eklentidir.<br/>Yöncü eklentilerini kurup kaldırmanızı ve özel ayarları yönetmenizi sağlar.
	        </div>
	        <div class="col-sm-3 text-center">
	        	<a href="?module=yoncu&Yoncu=yenidenyukle" class="btn btn-success">'.($Bilgi->Yoncu->Version==yoncu_config()['version']?'Yeniden Yükle':'Güncelle').'</a>
	        	<a href="configaddonmods.php#yoncu" class="btn btn-default">Yönet</a>
	        </div>
	    </div>
	</div>
    <div class="inset-element-container">
	    <div class="row">
	        <div class="col-sm-3 bottom-xs-margin">
	            <b>Domain Eklentisi</b>
	        </div>
	        <div class="col-sm-6 bottom-xs-margin">
	        	Bu Eklentiyi kurduğınızda <a href="configregistrars.php#yoncu">Domain Kayıt Operatörleri</a> menüsüne modül eklenmiş olacaktır. <a href="configregistrars.php#yoncu">Domain Kayıt Operatörleri</a> menüsünden modül detaylı ayarlarını yaparak <a href="configdomains.php">Domain Ücretlendirmesi</a> menüsünde uzantıların "Otomatik Kayıt" modülünü "Yöncü" seçerek otomatik alan adı kayıt ve yönetimini sağlayabilirsiniz.
	        </div>
	        <div class="col-sm-3 text-center">
	        	'.(!$DomainModul?'<a href="?module=yoncu&DomainModul=yukle" class="btn btn-success">Yükle</a>':'<a href="?module=yoncu&DomainModul=kaldir" class="btn btn-danger">Kaldır</a> <a href="configregistrars.php#yoncu" class="btn btn-default">Yönet</a>').'
	        </div>
	    </div>
	</div>
    <div class="inset-element-container">
	    <div class="row">
	        <div class="col-sm-3 bottom-xs-margin">
	            <b>Sunucu Eklentisi</b>
	        </div>
	        <div class="col-sm-6 bottom-xs-margin">
	        	Bu modül ile Yöncü üzerinden alınan sunucuları müşterilerinizin kendi panellerinden online olarak yönetmelerini sağlar, otomatik sipariş, kurulum, yönetme, resetleme, kapatma, açma, ip yönetimi gibi birçok işlem yapılabilir.
	        </div>
	        <div class="col-sm-3 text-center">
	        	'.(!$ServerModul?'<a href="?module=yoncu&ServerModul=yukle" class="btn btn-success">Yükle</a>':'<a href="?module=yoncu&ServerModul=kaldir" class="btn btn-danger">Kaldır</a> <a href="configproducts.php" class="btn btn-default">Yönet</a>').'
	        </div>
	    </div>
	</div>
    <div class="inset-element-container">
	    <div class="row">
	        <div class="col-sm-3 bottom-xs-margin">
	            <b>WhatsApp Bildiri</b>
	        </div>
	        <div class="col-sm-6 bottom-xs-margin">
	        	Bu modül Yöncü WhatsApp hizmeti üzerinden müşterilerinizin WhatsApp uygulamalarına bildiri mesajları göndermenizi sağlar. Destek talebi cevaplandı, üyelik oluşturuldu, hizmet aktif edildi, sipariş oluşturuldu, fatura ödendi gibi bildiriler gönderilebilir.
	        </div>
	        <div class="col-sm-3 text-center">
	        	'.(!$YoncuWhatsAppModul?'<a href="?module=yoncu&YoncuWhatsAppModul=yukle" class="btn btn-success">Yükle</a>':'<a href="?module=yoncu&YoncuWhatsAppModul=kaldir" class="btn btn-danger">Kaldır</a> <a href="setup/notifications/overview" class="btn btn-default">Yönet</a>').'
	        </div>
	    </div>
	</div>
</div>';
}
function yoncu_sidebar($vars){
    return <<<SIDEBAR
<span class="header"><img src="images/icons/products.png" class="absmiddle" width="16" height="16"> Yöncü Hizmetleri</span>
<ul class="menu">
	<li><a target="_blank" href="http://yoncu.com">Yöncü Web Sitesi</a></li>
	<li><a target="_blank" href="http://video.yoncu.com">Yöncü Eğitim Videoları</a></li>
	<li><a target="_blank" href="http://yoncu.com/forum">Yöncü Forum Sitesi</a></li>
	<li><a target="_blank" href="http://webmail.yoncu.com">Yöncü Web Mail</a></li>
	<li><a target="_blank" href="http://reklam.yoncu.com">Yöncü Reklam Banner</a></li>
</ul>
SIDEBAR;
}
function yoncu_clientarea($vars){
    return "yoncu_clientarea";
}
