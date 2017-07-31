<?php
$plan="";

if (isset($_POST['planId']) != null){
	if($_POST['planId']=='1'){
        $plan=1;
    }
    else if($_POST['planId']=='2'){
        $plan=2;
    }
    else if($_POST['planId']=='3'){
        $plan=3;
    }
    else{
        return false;
    }
}


$Config = array(
	'OemproURL' => 'http://fdgto.com/v3', // no trailing slash at the end. Example: http://mydomain.com/oempro
	'AdminUsername' => 'admin@fdgto.com',
	'AdminPassword' => '37W6eTa8e',
	'TargetPlanID' => $plan,// Enter the ID of the target user group ID
	'FromName' => 'MyESP',
	'FromEmail' => 'hello@fdgto.com',
);
// Configuration - End

$PageMessage = '';

Signup();

function Signup()
{
	global $PageMessage;

	if (isset($_POST['Command']) == false || $_POST['Command'] != 'Signup')
	{
		return;
	}

	if (VerifyInputData() == false)
	{
		return false;
	}

	print 'ok';
}


function VerifyInputData()
{
	global $PageMessage;


	if (isset($_POST['Name']) == false || $_POST['Name'] == '' || isset($_POST['Email']) == false || $_POST['Email'] == '' || $_POST['planId'] == '' || filter_var($_POST['Email'], FILTER_VALIDATE_EMAIL) == false)
	{
		$PageMessage = 'Please check and be sure that you have entered your name and email address properly';
		return false;
	}
    

	global $Config;

    

	$Parameters = array(
		'Command=Admin.Login',
		'ResponseFormat=JSON',
		'Username=' . $Config['AdminUsername'],
		'Password=' . $Config['AdminPassword'],
		'DisableCaptcha=true'
	);
	$Response = DataPostToRemoteURL($Config['OemproURL'] . '/api.php', $Parameters, 'POST', false, '', '', 30, false);

	if ($Response[0] != true)
	{
		$PageMessage = 'Internal error occurred. Failed to connect to system #1';
		return false;
	}

	$Response = json_decode($Response[1]);
	

	if (isset($Response->SessionID) == false || $Response->SessionID == '')
	{
		$PageMessage = 'Internal error occurred. Failed to connect to system #2';
		return false;
	}

	$AdminSessionID = $Response->SessionID;

	$RandomPassword = GenerateRandomString(5);

	$Parameters = array(
		'Command=User.Create',
		'ResponseFormat=JSON',
		'SessionID=' . $AdminSessionID,
		'RelUserGroupID=' . $Config['TargetPlanID'],
		'EmailAddress=' . $_POST['Email'],
		'Username=' . $_POST['Email'],
		'Password=' . $RandomPassword,
		'FirstName=' . $_POST['Name'],
		'LastName= ',
		'TimeZone=(GMT) London',
		'Language=en',
		'ReputationLevel=Untrusted',
		'CompanyName=',
		'Website=',
		'Street=',
		'City=',
		'State=',
		'Zip=',
		'Country=',
		'Phone=',
		'Fax=',
		'PreviewMyEmailAccount=',
		'PreviewMyEmailAPIKey=',
		'AvailableCredits=0',
	);
	$Response = DataPostToRemoteURL($Config['OemproURL'] . '/api.php', $Parameters, 'POST', false, '', '', 30, false);

	if ($Response[0] == false)
	{
		$PageMessage = 'Internal error occurred. Failed to connect to system #3';
		return false;
	}

	$Response = json_decode($Response[1]);

	if ($Response->Success == false && ($Response->ErrorCode == 12 || $Response->ErrorCode == 13))
	{
		$PageMessage = 'Entered email address has been already registered';
		return false;
	}
	elseif ($Response->Success == false)
	{
		$PageMessage = 'Internal error occurred. Error code is ' . $Response->ErrorCode;
		return false;
	}

	$EmailBody = <<<EOF
Hi {$_POST['Name']}!

Thanks for creating an account on MyESP. Below, you can find your login information:

URL: {$Config['OemproURL']}/app/index.php?/user/
Username: {$_POST['Email']}
Password: {$RandomPassword}

Login to your account, import your contacts and send your first email within minutes.

Any questions, let us know.

Cheers,
MyESP Team
EOF;

	mail($_POST['Email'], 'Your login information', $EmailBody, 'From: "' . $Config['FromName'] . '" <' . $Config['FromEmail'] . '>');

	header('Location: ' . $Config['OemproURL'] . '/app/index.php?/user/');
	exit;
}

function GenerateRandomString($CharLength = 7)
{
	$Characters = '123456789abcdefghijklmnopqrstuvwxyz';
	$CharactersLength = strlen($Characters);
	$TMPCounter = 0;
	$RandomString = '';
	while ($TMPCounter < ($CharLength - 2))
	{
		$RandomCharacter = mt_rand(1, $CharactersLength - 1);
		$RandomString .= $Characters[$RandomCharacter];
		$TMPCounter++;
	}

	$RandomString .= dechex(rand(10, 99) . rand(10, 99));

	return $RandomString;
}

function DataPostToRemoteURL($URL, $ArrayPostParameters, $HTTPRequestType = 'POST', $HTTPAuth = false, $HTTPAuthUsername = '', $HTTPAuthPassword = '', $ConnectTimeOutSeconds = 60, $ReturnHeaders = false)
{
	$PostParameters = implode('&', $ArrayPostParameters);

	$CurlHandler = curl_init();
	curl_setopt($CurlHandler, CURLOPT_URL, $URL);

	if ($HTTPRequestType == 'GET')
	{
		curl_setopt($CurlHandler, CURLOPT_HTTPGET, true);
	}
	elseif ($HTTPRequestType == 'PUT')
	{
		curl_setopt($CurlHandler, CURLOPT_PUT, true);
	}
	elseif ($HTTPRequestType == 'DELETE')
	{
		curl_setopt($CurlHandler, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($CurlHandler, CURLOPT_POST, true);
		curl_setopt($CurlHandler, CURLOPT_POSTFIELDS, $PostParameters);
	}
	else
	{
		curl_setopt($CurlHandler, CURLOPT_POST, true);
		curl_setopt($CurlHandler, CURLOPT_POSTFIELDS, $PostParameters);
	}

	curl_setopt($CurlHandler, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($CurlHandler, CURLOPT_CONNECTTIMEOUT, $ConnectTimeOutSeconds);
	curl_setopt($CurlHandler, CURLOPT_TIMEOUT, $ConnectTimeOutSeconds);
	curl_setopt($CurlHandler, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
	curl_setopt($CurlHandler, CURLOPT_SSL_VERIFYPEER, false);

	// The option doesn't work with safe mode or when open_basedir is set.
	if ((ini_get('safe_mode') != false) && (ini_get('open_basedir') != false))
	{
		curl_setopt($CurlHandler, CURLOPT_FOLLOWLOCATION, true);
	}

	if ($ReturnHeaders == true)
	{
		curl_setopt($CurlHandler, CURLOPT_HEADER, true);
	}
	else
	{
		curl_setopt($CurlHandler, CURLOPT_HEADER, false);
	}

	if ($HTTPAuth == true)
	{
		curl_setopt($CurlHandler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($CurlHandler, CURLOPT_USERPWD, $HTTPAuthUsername . ':' . $HTTPAuthPassword);
	}

	$RemoteContent = curl_exec($CurlHandler);

	if (curl_error($CurlHandler) != '')
	{
		return array(false, curl_error($CurlHandler));
	}

	curl_close($CurlHandler);

	return array(true, $RemoteContent);
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Pricing - MyESP</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
	<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->
	<script src="http://code.jquery.com/jquery.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>

	<style type="text/css" media="screen">
		.pricing-container {

		}

		.pricing-container ul {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.pricing-container ul li {
			margin-bottom: 5px;
			padding-bottom: 5px;
			border-bottom: 1px #ccc dotted;
		}

        li.signup{
            border-bottom: none !important;
            margin: 30px 0px;
        }
		li.pricing {
			margin-top: 20px;
			font-weight: bold;
			font-size: 26px;
		}
        .modal {
            text-align: center;
            padding: 0!important;
        }

        .modal:before {
            content: '';
            display: inline-block;
            height: 100%;
            vertical-align: middle;
            margin-right: -4px;
        }

        .modal-dialog {
            display: inline-block;
            text-align: left;
            vertical-align: middle;
        }
	</style>
</head>
<body>
    <div class="container">
	    <div class="row">
		    <div class="col-md-12" style="text-align:center;">
			    <h1>MyESP</h1>
			    <p class="lead">Plans and Pricing</p>
		    </div>
	    </div>

	    <div class="row">
		    <div class="col-md-12">
			    <div class="row pricing-container">
				    <div class="col-md-2 well col-md-offset-3" style="text-align:center; margin-top:20px; min-height:250px;">
					    <p style="font-size:1.3em; font-weight:bold;">Forever Free</p>
					    <ul>
                            <li>Store up to<br>500 subscribers</li>
                            <li>Send up to<br>2000 emails<br>every month</li>
                            <li class="pricing">Free!</li>
                            <li class="signup"><p class="text-center"><a class="btn btn-primary btn-lg open-AddBookDialog" role="button" data-toggle="modal" data-target="#login-modal" data-id="1" title="Plan one" href="#addBookDialog">SIGN UP</a></p></li>
					    </ul>
				    </div>
                    <div class="col-md-2 well" style="text-align:center; min-height:280px;">
                        <p style="font-size:1.3em; font-weight:bold;">Bronze Plan</p>
                        <ul>
                            <li>Store up to 2500 subscribers</li>
                            <li>Send up to 10,000 emails every month</li>
                            <li class="pricing">$25<span style="font-weight:normal;font-size:.8em;">/m</span></li>
                            <li class="signup"><p class="text-center"><a class="btn btn-primary btn-lg open-AddBookDialog" role="button" data-toggle="modal" data-target="#login-modal" data-id="2" title="Plan two" href="#addBookDialog">SIGN UP</a></p></li>
                        </ul>
                    </div>
				<div class="col-md-2 well" style="text-align:center; margin-top:20px; min-height:250px;">
					<p style="font-size:1.3em; font-weight:bold;">Gold Plan</p>
					<ul>
						<li>Store up to<br>5000 subscribers</li>
						<li>Send up to<br>20,000 emails<br>every month</li>
						<li class="pricing">$50<span style="font-weight:normal; font-size:.8em;">/m</span></li>
                        <li class="signup"><p class="text-center"><a class="btn btn-primary btn-lg open-AddBookDialog" role="button" data-toggle="modal" data-target="#login-modal" data-id="3" title="Plan three" href="#addBookDialog">SIGN UP</a></p></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

</div>

<?php if (isset($PageMessage) == true && $PageMessage != ''): ?>
<div class="col-sm-6 col-sm-offset-3">
<div class="alert alert-danger text-center"><?php print($PageMessage); ?></div><?php endif; ?>
</div>
</div>




<!-- BEGIN # MODAL LOGIN -->
<div class="modal fade" id="login-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    	<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header" align="center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
					</button>
                    <h3>Sign up now</h3>
			        <p>Sign up in seconds... No credit card required...</p>
				</div>
                
                <!-- Begin # DIV Form -->
                <div id="div-forms">
                    <!-- Begin # Login Form -->
                        <form role="form" method="post" action="plans.php">
                            <div class="modal-body " style="text-align:center;">

				            <div class="form-group">
					            <input type="text" class="form-control input-lg" id="Name" name="Name" placeholder="How should we call you?" required="required" value="<?php print(isset($_POST['Name']) == true && $_POST['Name'] != '' ? $_POST['Name'] : ''); ?>">
				            </div>
                            <div class="form-group">
                                <input type="email" class="form-control input-lg" id="Email" name="Email" placeholder="your@email.com" required="required" value="<?php print(isset($_POST['Email']) == true && $_POST['Email'] != '' ? $_POST['Email'] : ''); ?>">
                            </div>
                            <input type="hidden" id="planId" name="planId">
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success btn-lg" name="Command" value="Signup">Create my account</button>
                            </div>
                        </form>
		            </div>
                </div>  
			</div>
		</div>
	</div>
    <!-- END # MODAL LOGIN -->
</body>

<script>
    $(document).on("click", ".open-AddBookDialog", function () {
     var myplanId = $(this).data('id');
     $(".modal-body #planId").val( myplanId );
});


$(document).ready(function(){
    $(".show-modal").click(function(){
        $("#myModal").modal({
            backdrop: 'static',
            keyboard: false
        });
    });
});
</script>

</html>
