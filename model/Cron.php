<?php 
/*** COPYRIGHT NOTICE *********************************************************
 *
 * Copyright 2009-2017 ProjeQtOr - Pascal BERNARD - support@projeqtor.org
 * Contributors : -
 *
 * This file is part of ProjeQtOr.
 * 
 * ProjeQtOr is free software: you can redistribute it and/or modify it under 
 * the terms of the GNU Affero General Public License as published by the Free 
 * Software Foundation, either version 3 of the License, or (at your option) 
 * any later version.
 * 
 * ProjeQtOr is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for 
 * more details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * ProjeQtOr. If not, see <http://www.gnu.org/licenses/>.
 *
 * You can get complete code of ProjeQtOr, other resource, help and information
 * about contributors at http://www.projeqtor.org 
 *     
 *** DO NOT REMOVE THIS NOTICE ************************************************/

/* ============================================================================
 * ActionType defines the type of an issue.
 */ 
require_once('_securityCheck.php');

class Cron {

  // Define the layout that will be used for lists
    
  private static $sleepTime;
  private static $checkDates;
// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
  private static $checkNotifications;
// END - ADD BY TABARY - NOTIFICATION SYSTEM
  private static $checkImport;
  private static $checkEmails;
  private static $checkMailGroup;
  private static $runningFile;
  private static $timesFile;
  private static $stopFile;
  private static $errorFile;
  private static $deployFile;
  private static $restartFile;
  private static $cronWorkDir;
  public static $listCronExecution;
  public static $lastCronTimeExecution;
  public static $lastCronExecution;
  
   /** ==========================================================================
   * Constructor
   * @param $id the id of the object in the database (null if not stored yet)
   * @return void
   */ 
  function __construct($id = NULL, $withoutDependentObjects=false) {

  }

  
   /** ==========================================================================
   * Destructor
   * @return void
   */ 
  function __destruct() {
    
  }

// ============================================================================**********
// GET STATIC DATA FUNCTIONS
// ============================================================================**********
  
  public static function init() {
  	if (self::$cronWorkDir) return;
  	self::$cronWorkDir=Parameter::getGlobalParameter('cronDirectory');
    self::$runningFile=self::$cronWorkDir.'/RUNNING';
    self::$timesFile=self::$cronWorkDir.'/DELAYS';
    self::$stopFile=self::$cronWorkDir.'/STOP';
    self::$errorFile=self::$cronWorkDir.'/ERROR';
    self::$deployFile=self::$cronWorkDir.'/DEPLOY';
    self::$restartFile=self::$cronWorkDir.'/RESTART';
  }
  
  public static function getActualTimes() {
  	self::init();
  	if (! is_file(self::$timesFile)) {
  		return array();
  	}
  	$handle=fopen(self::$timesFile, 'r');
    $line=fgets($handle);
    fclose($handle);
    $result=array();
    $arr=explode('|',$line);
    foreach ($arr as $val) {
    	$split=explode('=',$val);
    	if (count($split)==2) {
    	  $result[$split[0]]=$split[1];
    	}
    }
  	return $result;
  }

  public static function setActualTimes() {
  	self::init();
    $handle=fopen(self::$timesFile, 'w');
    fwrite($handle,'SleepTime='.self::getSleepTime()
                 .'|CheckDates='.self::getCheckDates()
                 .'|CheckImport='.self::getCheckImport()
                 .'|CheckEmails='.self::getCheckEmails()
                 .( Mail::isMailGroupingActiv() ?'|CheckMailGroup='.self::getCheckMailGroup():'')
// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM            
                 .(isNotificationSystemActiv()?'|CheckNotifications='.self::getCheckNotifications():'')
// END - ADD BY TABARY - NOTIFICATION SYSTEM
           );
    fclose($handle);
  }
  
  public static function getSleepTime() {
  	self::init();
    if (self::$sleepTime) {
    	return self::$sleepTime;
    }
  	$cronSleepTime=Parameter::getGlobalParameter('cronSleepTime');
    if (! $cronSleepTime) {$cronSleepTime=10;}
    self::$sleepTime=$cronSleepTime;
    return self::$sleepTime;
  }

  public static function getCheckDates() {
  	self::init();
    if (self::$checkDates) {
      return self::$checkDates;
    }
    $checkDates=Parameter::getGlobalParameter('cronCheckDates'); 
    if (! $checkDates) {$checkDates=30;}
    self::$checkDates=$checkDates;
    return self::$checkDates;
  }

// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
  public static function getCheckNotifications() {
    self::init();
    if (!isNotificationSystemActiv()) {
        self::$checkNotifications=-1;
        return self::$checkNotifications;        
    }  
    if (self::$checkNotifications) {
      return self::$checkNotifications;
    }
    $checkNotifications=Parameter::getGlobalParameter('cronCheckNotifications'); 
    if (! $checkNotifications) {$checkNotifications=3600;}
    self::$checkNotifications=$checkNotifications;
    return self::$checkNotifications;
  }
// END - ADD BY TABARY - NOTIFICATION SYSTEM

  public static function getCheckImport() {
  	self::init();
    if (self::$checkImport) {
      return self::$checkImport;
    }
    $checkImport=Parameter::getGlobalParameter('cronCheckImport'); 
    if (! $checkImport) {$checkImport=30;}
    self::$checkImport=$checkImport;
    return self::$checkImport;
  }  
  
  public static function getCheckEmails() {
    self::init();
    if (self::$checkEmails) {
      return self::$checkEmails;
    }
    $checkEmails=Parameter::getGlobalParameter('cronCheckEmails'); 
    if (! $checkEmails) {$checkEmails=5*60;} // Default=every 5 mn
    self::$checkEmails=$checkEmails;
    return self::$checkEmails;
  }  
  public static function getCheckMailGroup() {
    self::init();
    if (self::$checkMailGroup) {
      return self::$checkMailGroup;
    }
    $checkMailGroup=Mail::getMailGroupPeriod(); 
    if (! $checkMailGroup or $checkMailGroup<0) {
      $checkMailGroup=-1;
    } else {
      $checkMailGroup=$checkMailGroup/2; // Check every half period
      if ($checkMailGroup<self::getSleepTime()) {
        $checkMailGroup=self::getSleepTime();
      } else if ($checkMailGroup>60) { // check at least every minute;
        $checkMailGroup=60;
      }
    }
    self::$checkMailGroup=$checkMailGroup;
    return self::$checkMailGroup;
  }  
  
  public static function check() {
  	self::init();
    if (file_exists(self::$runningFile)) {
      $handle=fopen(self::$runningFile, 'r');
      $last=fgets($handle);
      $now=time();
      fclose($handle);
      if ( ($now-$last) > (self::getSleepTime()*5)) {
        // not running for more than 5 cycles : dead process
        self::removeRunningFlag();
        return "stopped";
      } else {
        return "running";
      }
    } else {
      return "stopped";
    }
  }
  
  public static function abort() {
  	self::init();
    errorLog('cron abnormally stopped');
    if (file_exists(self::$runningFile)) {
  	  unlink(self::$runningFile);
    }
    
    $errorFileName=self::$errorFile.'_'.date('Ymd_His');
    $mode=(file_exists($errorFileName))?'w':'x';
    $errorFile=fopen($errorFileName, $mode);
    fclose($errorFile);  
  } 
  
  public static function removeStopFlag() {
  	self::init();
    if (file_exists(self::$stopFile)) {
      unlink(self::$stopFile);
    }
  }
  
  public static function removeRunningFlag() {
  	self::init();
    if (file_exists(self::$runningFile)) {
      unlink(self::$runningFile);
    }
  }
  public static function removeDeployFlag() {
    if (file_exists(self::$deployFile)) {
      unlink(self::$deployFile);
    }
  }
  public static function removeRestartFlag() {
    if (file_exists(self::$restartFile)) {
      unlink(self::$restartFile);
    }
  }
  public static function setRunningFlag() {
  	self::init();
  	$handle=fopen(self::$runningFile, 'w');
    fwrite($handle,time());
    fclose($handle);
  }
  
  public static function setRestartFlag() {
    self::init();
    $handle=fopen(self::$restartFile, 'w');
    fwrite($handle,time());
    fclose($handle);
  }
  
  public static function setStopFlag() {
  	self::init();
    $handle=fopen(self::$stopFile, 'w');
    fclose($handle);
  }
  
  public static function checkStopFlag() {
  	self::init();
    if (file_exists(self::$stopFile) or file_exists(self::$deployFile)) { 
      traceLog('Cron normally stopped at '.date('d/m/Y H:i:s'));
      self::removeRunningFlag();
      self::removeStopFlag();
      if (file_exists(self::$deployFile)) {
      	traceLog('Cron stopped for deployment. Will be restarted');
      	self::setRestartFlag();
        self::removeDeployFlag();
      }
      return true; 
    } else {
    	return false;
    }
  }
  
  // Restrart already running CRON  !!! NOT WORKING !!!
  public static function restart() {
    error_reporting(0);
    //session_write_close();
    if (self::check()=='running') {
      self::setStopFlag();
      sleep(self::getSleepTime());
    }
    self::setRestartFlag();
    //self::relaunch(); // FREEZES CURRENT USER
  }
  
	// If running flag exists and cron is not really running, relaunch
	public static function relaunch() {
		self::init();
		if (file_exists(self::$restartFile)) {
			self::removeRestartFlag();
			self::run();
		} else if (file_exists(self::$runningFile)) {
      $handle=fopen(self::$runningFile, 'r');
      $last=fgets($handle);
      $now=time();
      fclose($handle);
      if (!$last or !is_numeric($last)) $last=0;
      if ( (!$last or $now-$last) > (self::getSleepTime()*5)) {
        // not running for more than 5 cycles : dead process
        self::removeRunningFlag();
        self::run();
      }
		} else {
		  
		}
	}
	
	public static function run() {
//scriptLog('Cron::run()');	
    global $cronnedScript, $i18nMessages, $currentLocale;
    $cronnedScript=true; // Defined and set to be able to force rights on Control() : Cron has all rights.
    self::init();  
    $i18nMessages=null;
    $currentLocale=Parameter::getGlobalParameter ( 'paramDefaultLocale' );
		if (self::check()=='running') {
      errorLog('Try to run cron already running');
      return;
    }
    $inCronBlockFonctionCustom=true;
    self::removeDeployFlag();
    self::removeRestartFlag();
    projeqtor_set_time_limit(0);
    ignore_user_abort(1);
    error_reporting(0);
    session_write_close();
    error_reporting(E_ERROR);
// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
    $cronCheckNotifications=-1;
    if (isNotificationSystemActiv()) {
        $cronCheckNotifications=self::getCheckNotifications();
    }
// END - ADD BY TABARY - NOTIFICATION SYSTEM
    $cronCheckDates=self::getCheckDates();
    $cronCheckImport=self::getCheckImport();
    $cronCheckEmails=self::getCheckEmails();
    $cronCheckMailGroup=self::getCheckMailGroup();
    $cronSleepTime=self::getSleepTime();
    self::setActualTimes();
    self::removeStopFlag();
    self::setRunningFlag();
    traceLog('Cron started at '.date('d/m/Y H:i:s')); 
    while(1) {
      if (self::checkStopFlag()) {
        return; 
      }
      Sql::reconnect(); // Force reconnection to avoid "mysql has gone away"
      self::setRunningFlag();
      // CheckDates : automatically raise alerts based on dates
      if ($cronCheckDates>0) {
	      $cronCheckDates-=$cronSleepTime;
	      if ($cronCheckDates<=0) {
	      	try { 
	          self::checkDates();
	      	} catch (Exception $e) {
	      		traceLog("Cron::run() - Error on checkDates()");
	      	}
	        $cronCheckDates=Cron::getCheckDates();
	      }
      }
      // CheckImport : automatically import some files in import directory
      if ($cronCheckImport>0) {
	      $cronCheckImport-=$cronSleepTime;
	      if ($cronCheckImport<=0) {
	      	try { 
	          self::checkImport();
	      	} catch (Exception $e) {
	          traceLog("Cron::run() - Error on checkImport()");
	        }
	        $cronCheckImport=Cron::getCheckImport();
	      }
      }
      // CheckEmails : automatically import notes from Reply to mails
      if ($cronCheckEmails>0) {
	      $cronCheckEmails-=$cronSleepTime;
	      if ($cronCheckEmails<=0) {
	        try { 
	          self::checkEmails();
	        } catch (Exception $e) {
	          traceLog("Cron::run() - Error on checkEmails()");
	        }
	        $cronCheckEmails=Cron::getCheckEmails();
	      }
      }
      // CheckEmails : automatically import notes from Reply to mails
      if ($cronCheckMailGroup>0) {
        $cronCheckMailGroup-=$cronSleepTime;
        if ($cronCheckMailGroup<=0) {
          try {
            self::checkMailGroup();
          } catch (Exception $e) {
            traceLog("Cron::run() - Error on checkMailGroup()");
          }
          $cronCheckMailGroup=Cron::getCheckMailGroup();
        }
      }
      // Check Database Execution
      foreach (self::$listCronExecution as $key=>$cronExecution){
        if($cronExecution->nextTime==null){
          $cronExecution->calculNextTime();
        }
        $UTC=new DateTimeZone(Parameter::getGlobalParameter ( 'paramDefaultTimezone' ));
        $date=new DateTime('now');
        if(file_exists($cronExecution->fileExecuted) && $cronExecution->nextTime!=null && $cronExecution->nextTime<=$date->format("U")){
          self::$lastCronTimeExecution = $cronExecution->nextTime;
          self::$lastCronExecution = $cronExecution->cron;
          $cronExecution->calculNextTime();
          call_user_func($cronExecution->fonctionName);
        }
      }
      
// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
      // CheckNotifications : automatically generate notifications
      if (isNotificationSystemActiv() and $cronCheckNotifications>0 ) {
        $cronCheckNotifications-=$cronSleepTime;
        if ($cronCheckNotifications<=0) {
          try { 
            self::checkNotifications();
          } catch (Exception $e) {
            traceLog("Cron::run() - Error on checkNotifications()");
          }
          $cronCheckNotifications=Cron::getCheckNotifications();
        }
      }
// END - ADD BY TABARY - NOTIFICATION SYSTEM
		
      // Sleep to next check
      sleep($cronSleepTime);
    } // While 1
  }
  
// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
  public static function checkNotifications() {      
//scriptLog('Cron::checkNotifications()');
    global $globalCronMode;
    if (!isNotificationSystemActiv()) {exit;}
    self::init();
    $globalCronMode=true;  
    // Generates notification from notification Definition
    $notifDef = new NotificationDefinition();
    $crit = array("idle" => '0');
    $lstNotifDef=$notifDef->getSqlElementsFromCriteria($crit);    
    foreach($lstNotifDef as $notifDef) {
        $notifDef->generateNotifications();
  }
  
    // Generates email notification
    $currentDate = new DateTime();
    $theCurrentDate = $currentDate->format('Y-m-d');
    $crit = array(
                    "idle" => '0',
                    "sendEmail" => '1',
                    "emailSent" => '0',
                    "notificationDate" => $theCurrentDate,
                 );
    $notif = new Notification();
    $lstNotif = $notif->getSqlElementsFromCriteria($crit);
    foreach($lstNotif as $notif) {
      if ($notif->notificationTime->format('H:i:s')>=$currentDate->format('H:i:s')) {
        $notif->sendEmail();
      }
    }
  }// END - ADD BY TABARY - NOTIFICATION SYSTEM
    
  public static function checkDates() {
//scriptLog('Cron::checkDates()');
  	global $globalCronMode;
    self::init();
    $globalCronMode=true;  
    $indVal=new IndicatorValue();
    $where="idle='0' and (";
	  // If YEARLY, even if warning and alert have been sent, check if we need to update targetDateTime
    $where.=" ( warningTargetDateTime<='" . date('Y-m-d H:i:s') . "' and (warningSent='0' or code = 'YEARLY'))" ;
    $where.=" or ( alertTargetDateTime<='" . date('Y-m-d H:i:s') . "' and (alertSent='0' or code = 'YEARLY'))" ;
    $where.=")";
    $lst=$indVal->getSqlElementsFromCriteria(null, null, $where);

    foreach ($lst as $indVal) {
      $indVal->checkDates();
    }
  }
  
  public static function checkImport() {
//scriptLog('Cron::checkImport()');
    self::init();
  	global $globalCronMode, $globalCatchErrors;
    $globalCronMode=true;   	
    $globalCatchErrors=true;
  	$importDir=Parameter::getGlobalParameter('cronImportDirectory');
  	$eol=Parameter::getGlobalParameter('mailEol');
  	$cpt=0;
  	$pathSeparator=Parameter::getGlobalParameter('paramPathSeparator');
  	$importSummary="";
  	$importFullLog="";
  	$attachmentArray=array();
  	$boundary = null;
  	if (is_dir($importDir)) {
      if ($dirHandler = opendir($importDir)) {
        while (($file = readdir($dirHandler)) !== false) {
        	if ($file!="." and $file!=".." and filetype($importDir . $pathSeparator . $file)=="file") {
        		$globalCronMode=true; // Cron should not be stopped on error or exception
            $importFile=$importDir . $pathSeparator . $file;      
            $split=explode('_',$file);
            $class=$split[0];
            $result="";
            try {
              $result=Importable::import($importFile, $class);
            } catch (Exception $e) {
            	$msg="CRON : Exception on import of file '$importFile'";
            	$result="ERROR";
            }
            $globalCronMode=false; // VOLOUNTARILY STOP THE CRON. Actions are requested !
            try {
	            if ($result=="OK") {	            	
	              $msg="Import OK : file $file imported with no error [ Number of '$class' imported : " . Importable::$cptDone . " ]";
	              traceLog($msg);
	              $importSummary.="<span style='color:green;'>$msg</span><br/>";
	              if (! is_dir($importDir . $pathSeparator . "done")) {
	              	mkdir($importDir . $pathSeparator . "done",0777,true);
	              	
	              }
	              rename($importFile,$importDir . $pathSeparator . "done" . $pathSeparator . $file);
	            } else {
	            	if ($result=="INVALID") {
	               	$msg="Import INVALID : file $file imported with " . Importable::$cptInvalid . " control errors [ Number of '$class' imported : " . Importable::$cptOK . " ]";
	               	traceLog($msg);
                  $importSummary.="<span style='color:orange;'>$msg</span><br/>";
	              } else {
	            	  $msg="Import ERROR : file $file imported with " . Importable::$cptRejected . " errors [ Number of '$class' imported : " . Importable::$cptOK . " ]";
	            	  traceLog($msg);
                  $importSummary.="<span style='color:red;'>$msg</span><br/>";
	              }
	              if (! is_dir($importDir . $pathSeparator . "error")) {
	                mkdir($importDir . $pathSeparator . "error",0777,true);
	              }
	            	rename($importFile,$importDir . $pathSeparator . "error" . $pathSeparator . $file);
	            }
            } catch (Exception $e) {
            	$msg="CRON : Impossible to move file '$importFile'";
            	traceLog($msg);
              $importSummary.="<span style='color:red;'>$msg</span><br/>";
            	$msg="CRON IS STOPPED TO AVOID MULTIPLE-TREATMENT OF SAME FILES";
            	traceLog($msg);
              $importSummary.="<span style='color:red;'>$msg</span><br/>";
            	$msg="Check access rights to folder '$importDir', subfolders 'done' and 'error' and file '$importFile'";
            	traceLog($msg);
              $importSummary.="<span style='color:red;'>$msg</span><br/>";
            	exit; // VOLOUNTARILY STOP THE CRON. Actions are requested !
            }
            $globalCronMode=true; // If cannot write log file, do not exit CRON (not blocking)
            $logFile=$importDir . $pathSeparator . 'logs' . $pathSeparator . substr($file, 0, strlen($file)-4) . ".log.htm";
        	  if (! is_dir($importDir . $pathSeparator . "logs")) {
              mkdir($importDir . $pathSeparator . "logs",0777,true);
            }
            if (file_exists($logFile)) {
            	kill($logFile);
            }
            // Write log file
            $fileHandler = fopen($logFile, 'w');
            fwrite($fileHandler, Importable::getLogHeader());
            fwrite($fileHandler, Importable::$importResult);
            fwrite($fileHandler, Importable::getLogFooter());
            fclose($fileHandler);
            // Prepare joined file on email
        	  if (Parameter::getGlobalParameter('cronImportLogDestination')=='mail+log') {
        	  	if (! isset($paramMailerType) or $paramMailerType=='phpmailer') {
        	  		$attachmentArray[]=$logFile;
        	  	} else { // old way to send attachments
	        	  	if (! $boundary) {
	        	  	  $boundary = md5(uniqid(microtime(), TRUE));
	        	  	}
							  $file_type = 'text/html';
	              $content = Importable::getLogHeader();
							  $content .= Importable::$importResult;
							  $content .= Importable::getLogFooter();
							  $content = chunk_split(base64_encode($content));       
	              $importFullLog .= $eol.'--'.$boundary.$eol;
	              $importFullLog .= 'Content-type:'.$file_type.';name="'.basename($logFile).'"'.$eol;
	              $importFullLog .= 'Content-Length: ' . strlen($content).$eol;     
	              $importFullLog .= 'Content-transfer-encoding:base64'.$eol;
	              $importFullLog .= 'Content-disposition: attachment; filename="'.basename($logFile).'"'.$eol; 
	              $importFullLog .= $eol.$content.$eol;
	              $importFullLog .= '--'.$boundary.$eol;
        	  	}
            }
            $cpt+=1;
        	}
        }
        closedir($dirHandler);
      }
    } else {
    	$msg="ERROR - check Cron::Import() - ". $importDir . " is not a directory";
    	traceLog($msg);
      $importSummary.="<span style='color:red;'>$msg</span><br/>";
    }
    if ($importSummary) {
	    $logDest=Parameter::getGlobalParameter('cronImportLogDestination');
	    if (stripos($logDest,'mail')!==false) {
	    	$baseName=Parameter::getGlobalParameter('paramDbDisplayName');
	    	$to=Parameter::getGlobalParameter('cronImportMailList');
	    	if (! $to) {
	    		traceLog("Cron : email requested, but no email address defined");
	    	} else {
		      $message=$importSummary;
		      if (stripos($logDest,'log')!==false) {
		      	$message=Importable::getLogHeader().$message;
		      	if($importFullLog) $message.=$eol.$importFullLog;
		      	Importable::getLogFooter();
		      }
	        $title="[$baseName] Import summary ". date('Y-m-d H:i:s');
	        $resultMail=sendMail($to, $title, $message, null, null, null, $attachmentArray, $boundary);	        
	    	}
	    }
    }
  }
  
  
  public static function checkEmails() {	
  	self::init();
    global $globalCronMode, $globalCatchErrors;
    $globalCronMode=true;     
    $globalCatchErrors=true;
    require_once("../model/ImapMailbox.php"); // Imap management Class
		
    if (! ImapMailbox::checkImapEnabled()) {
      traceLog("ERROR - Cron::checkEmails() - IMAP extension not enabled in your PHP config. Cannot connect to IMAP Mailbox.");
      return;
    }
    $checkEmails=Parameter::getGlobalParameter('cronCheckEmails');
    if (!$checkEmails or intval($checkEmails)<=0) {
      return; // disabled
    }
		// IMAP must be enabled in Google Mail Settings
		$emailEmail=Parameter::getGlobalParameter('cronCheckEmailsUser');
		$emailPassword=Parameter::getGlobalParameter('cronCheckEmailsPassword');
		//$emailAttachmentsDir=dirname(__FILE__) . '/../files/attach';
		$paramAttachDir=Parameter::getGlobalParameter('paramAttachmentDirectory');
		$pathSeparator=Parameter::getGlobalParameter('paramPathSeparator');
		if (substr($paramAttachDir,0,2)=='..') {
		  $curdir=dirname(__FILE__);
		  $paramAttachDir=str_replace(array('/model','\model'),array('',''),$curdir).substr($paramAttachDir,2);
		}
		$emailAttachmentsDir=((substr($paramAttachDir,-1)=='\\' or substr($paramAttachDir,-1)=="/")?$paramAttachDir:$paramAttachDir.$pathSeparator).'emails';
		if (! file_exists($emailAttachmentsDir)) {
		  mkdir($emailAttachmentsDir,0777,true);
		}
		$emailHost=Parameter::getGlobalParameter('cronCheckEmailsHost'); // {imap.gmail.com:993/imap/ssl}INBOX';
		if (! $emailHost) {
			traceLog("IMAP connection string not defined");
			return;
		}
		$mailbox = new ImapMailbox($emailHost, $emailEmail, $emailPassword, $emailAttachmentsDir, 'utf-8');
		$mails = array();
		
		// Get some mail
		$mailsIds = $mailbox->searchMailBox('UNSEEN UNDELETED');
		if(!$mailsIds) {
		  debugTraceLog('Mailbox is empty'); // Will be a debug level trace
		  return;
		}
		
	  foreach ($mailsIds as $mailId) {
  		$mail = $mailbox->getMail($mailId);
  		$mailbox->markMailAsUnread($mailId);
  		
  		$body=$mail->textPlain;
  		$bodyHtml=$mail->textHtml;
  		if (!$body and $bodyHtml) {		
  		  $toText=new Html2Text($bodyHtml);
  			$body=$toText->getText();
  		}
  		$class=null;
  		$id=null;
  		$msg=null;
  		$senderId=null;	
  		// Class and Id of object
  		$posClass=strpos($body,'directAccess=true&objectClass=');
  		if ($posClass) { // It is a ProjeQtor mail
  		  $posId=strpos($body,'&objectId=',$posClass);
  		  $posEnd=strpos($body,'>',$posId);
  		  $class=substr($body,$posClass+30,$posId-$posClass-30);
  		  $id=substr($body,$posId+10,$posEnd-$posId-10);
  		} else {	
  			continue;
  		}
  		// Search end of Message (this is valid for text only, treatment of html messages would require other code)  		
  		$posEndMsg=strpos($body,"\r\n>"); // Search for Thunderbird and Gmail
  		if ($posEndMsg) {
  		  $posEndMsg=strrpos(substr($body,0,$posEndMsg-20), "\r\n");
  		  /*if ($posEndMsg) {
  		    $posEndMsg=strrpos(substr($body,0,$posEndMsg-20), "\r\n");
  		    $previousLine=strrpos(substr($body,0,$posEndMsg-20), "\r\n");
  		    if ($previousLine and preg_match('/<.*?@.*?>/',substr($body,$previousLine,$posEndMsg-$previousLine+1)) ) {
  		      $posEndMsgNew=strrpos(substr($body,0,$posEndMsg-2), "\r\n");
  		      if ($posEndMsgNew) $posEndMsg=$posEndMsgNew;
  		    }
  		  }*/
  		} else {
  		  $posEndMsg=strpos($body,"\n>");
  		  /*if ($posEndMsg) {
  		    $posEndMsg=strrpos(substr($body,0,$posEndMsg-20), "\n");
  		    $previousLine=strrpos(substr($body,0,$posEndMsg-20), "\n");
  		    if ($previousLine and preg_match('/<.*?@.*?>/',substr($body,$previousLine,$posEndMsg-$previousLine+1)) ) {
  		      $posEndMsgNew=strrpos(substr($body,0,$posEndMsg-2), "\n");
  		      if ($posEndMsgNew) $posEndMsg=$posEndMsgNew;
  		    }
  		  }*/ 
  		}
  		if (!$posEndMsg) { // Search for outlook
  		  preg_match('/<.*?@.*?> [\r\n]/',$body, $matches);
  		  if (count($matches)>0) {
  		    $posEndMsg=strpos($body, $matches[0]);
  		    $posEndMsg=strrpos(substr($body,0,$posEndMsg-2), "\r\n");
  		  }
  		}
  		if (!$posEndMsg) {
  		  $posEndMsg=strpos($body,"\r\n\r\n\r\n");
  		  if (!$posEndMsg) {
  		    $posEndMsg=strpos($body,"\n\n\n");
  		  }
  		}

  		if ($posEndMsg) {
  		  $msg=substr($body,0,$posEndMsg);
  		}
  		// Remove unexpected "tags" // Valid as lon as we treat emails as text
  		$msg=preg_replace('/<mailto.*?\>/','',$msg);
  		$msg=preg_replace('/<http.*?\>/','',$msg);
  		$msg=preg_replace('/<#[A-F0-9\-]*?\>/','',$msg);
  		$msg=str_replace(" \r\n","\r\n",$msg);
  		$msg=str_replace(" \r\n","\r\n",$msg);
  		$msg=str_replace("\r\n\r\n\r\n","\r\n\r\n",$msg);
  		$msg=str_replace("\r\n\r\n\r\n","\r\n\r\n",$msg);
  		$msg=str_replace(" \n","\n",$msg);
  		$msg=str_replace(" \n","\n",$msg);
  		$msg=str_replace("\n\n\n","\n\n",$msg);
  		$msg=str_replace("\n\n\n","\n\n",$msg);
  		
  		// Sender
  		$sender=$mail->fromAddress;
  		$crit=array('email'=>$sender);
  		$usr=new Affectable();
  		$usrList=$usr->getSqlElementsFromCriteria($crit,false,null,'idle asc, isUser desc, isResource desc');
  		if (count($usrList)) {
  		  $senderId=$usrList[0]->id;
  		}
  		if (! $senderId) {
  			traceLog("Email message received from '$sender', not recognized as resource or user or contact : message not stored as note to avoid spamming");
  			$mailbox->markMailAsUnread($mailId);
  			continue;
  		}
  		$arrayFrom=array("\n","\r"," ");
  		$arrayTo=array("","","");
  		$class=str_replace($arrayFrom, $arrayTo, $class);
  		$id=str_replace($arrayFrom, $arrayTo, $id);	
      $obj=null;
      if (SqlElement::class_exists($class) and is_numeric($id)) {
        $obj=new $class($id);
      }
  		if ($obj and $obj->id and $senderId) {
  		  $note=new Note();
  		  $note->refType=$class;
  		  $note->refId=$id;
  		  $note->idPrivacy=1;
  		  $note->note=nl2brForPlainText($msg);
  		  $note->idUser=$senderId;
  		  $note->creationDate=date('Y-m-d H:i:s');
  		  $note->fromEmail=1;
  		  $note->save();
  		  $mailbox->markMailAsRead($mailId);
  		  debugTraceLog("Note from '$sender' added on $class #$id");
  		} else {
  		  $mailbox->markMailAsUnread($mailId);
  		}
    }
    // Clean $emailAttachmentsDir for php files
    foreach(glob($emailAttachmentsDir.'/*') as $v) {
      if (! is_file($v)) continue;
      if (substr(strtolower($v),-4)=='.php' or substr(strtolower($v),-5,4)=='.php') unlink($v);
      //if (is_file($v)) unlink($v);
    }
  }
  
  public static function checkMailGroup() {
    self::init();
    global $globalCronMode, $globalCatchErrors;
    $globalCronMode=true;
    $globalCatchErrors=true;
    $period=Mail::getMailGroupPeriod();
    if ($period<=0) return;
    // Direct SQL : allowed here because very technical query, requiring high performance
    //              attention, in postgresql, fields are always returned in lowercase
    $mts=new MailToSend();
    $mtsTable=$mts->getDatabaseTableName();
    $dateToCheck=date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s")) - $period);
    // Get list of items with last stored email (in MailToSend) older than period : must send the emails 
    $query="select refType as reftype, refId as refid, max(recordDateTime) as lastdate from $mtsTable group by refType, refId having max(recordDateTime)<'$dateToCheck'";
    $result = Sql::query($query);
    $arrayMailToSend=array();
    if (Sql::$lastQueryNbRows > 0) {
      $line = Sql::fetchLine($result);
      while ($line) {
        $arrayMailToSend[]=array('refType'=>$line['reftype'], 'refId'=>$line['refid'],'date'=>$line['lastdate']);
        $line = Sql::fetchLine($result);
      }
    } else {
      return;
    }
    // Here, $arrayMailToSend contains 1 line per element for wich mails have to be sent 
    $error=false;
    Sql::beginTransaction();
    $groupRule=Parameter::getGlobalParameter('mailGroupDifferent');
    if (!$groupRule) $groupRule='LAST';
    $idToPurge=array();
    $sepLine="<table style='width:95%'><tr><td style='border-bottom:3px solid #545381'>&nbsp;</td></tr><tr><td>&nbsp;</td></tr></table>";
    foreach ($arrayMailToSend as $toSendItem) { // For each item in $arrayMailToSend
      // List all emails stored in MailToSend for the item
      $refType=$toSendItem['refType'];
      $refId=$toSendItem['refId'];
      $crit=array('refType'=>$refType, 'refId'=>$refId);
      $list=$mts->getSqlElementsFromCriteria($crit,false,null,'recordDateTime desc');
      $item=new $refType($refId);
      $arrayMail=array();
      $last=end($list);
      $lastDate=$last->recordDateTime;
      foreach ($list as $toSend) { // For each email to send
        if ($toSend->recordDateTime>$toSendItem['date']) continue; // Found a brand new email, do not take it into account, will be included in next period loop 
        $idToPurge[]=$toSend->id; // Store ids of MailToSend that need to be purge after sending email
        $key=0; // For $groupRule=='ALL' or $groupRule=='MERGE'
        if ($groupRule=='ALL') $key=$toSend->idEmailTemplate;
        if ( !isset($arrayMail[$key])) {
          if ($toSend->template=='basic') {
            $template=$item->getMailDetail();
          } else {
            $templateObj=new EmailTemplate($toSend->idEmailTemplate);
            $template=$item->getMailDetailFromTemplate($templateObj->template,$lastDate);
          }
          $arrayMail[$key]=array(
            'newerDate'=>$toSend->recordDateTime,
            'olderdate'=>$toSend->recordDateTime,
            'idEmailTemplate'=>$toSend->idEmailTemplate,
            'nameTemplate'=>$toSend->template,
            'template'=>$template,
            'title'=>$toSend->title,
            'allTitles'=>array($toSend->title),
            'allDates'=>array($toSend->recordDateTime),
            'allTemplates'=>array($toSend->template),
            'dest'=>$toSend->dest      
          );
        } else {
          // Merge dest
          $arr1=explode(',',$arrayMail[$key]['dest']);
          $arr2=explode(',',$toSend->dest);
          $arrMerged=array_unique(array_merge($arr1, $arr2));
          $arrayMail[$key]['dest']=implode(',', $arrMerged);
          // Merge titles
          $arrayMail[$key]['allTitles'][]=$toSend->title;
          $arrayMail[$key]['allDates'][]=$toSend->recordDateTime;
          // Merge template (if option is to merge templates)
          if ($groupRule=='MERGE' and ! in_array($toSend->template, $arrayMail[$key]['allTemplates'])) {
            $arrayMail[$key]['allTemplates'][]=$toSend->template;
            $body=$arrayMail[$key]['template'];
            if ($toSend->template=='basic') {
              $template=$item->getMailDetail();
            } else {
              $templateObj=new EmailTemplate($toSend->idEmailTemplate);
              $template=$item->getMailDetailFromTemplate($templateObj->template,$lastDate);
            }
            $body.=$sepLine.$template;
            $arrayMail[$key]['template']=$body;
          }
        }
      }
      foreach ($arrayMail as $mail) {
        $dest=$mail['dest'];
        $title=$mail['title'];
        $body='<html>';
        $body.='<head><title>' . $title .'</title></head>';
        $body.='<body style="font-family: Verdana, Arial, Helvetica, sans-serif;">';
        if (count($mail['allTitles'])>1) {
          $body.="<table style='width:95%'>";
          $body.="<tr><td colspan='2' style='text-align:center;background-color: #E0E0E0;font-weight:bold'>".i18n("mailGroupTitles")."</td></tr>";
          foreach ($mail['allTitles'] as $idx=>$title) {
            $body.="<tr><td style='width:10%;padding:3px 10px'>".htmlFormatDateTime($mail['allDates'][$idx])."</td><td style='padding:3px 10px'>$title</td></tr>";
          }
          $body.="";
          $body.="";
          $body.="</table>";
          $body.=$sepLine;
        }
        $body.=$mail['template'];
        $body.='</body>';
        $body.='</html>';
        $resultMail[] = sendMail($dest, $title, $body, $item, null, null, null, null, null );
      }
    }
    
    // Puge sent emails from MailToSend
    $listId=implode(',',$idToPurge);
    $resPurge=$mts->purge("id in ($listId)");
    
    // Finalize
    if ($error) {
      Sql::rollbackTransaction();
    } else {
      Sql::commitTransaction();
    }
  }
}

//Look if CronExecution exist in database
Cron::$listCronExecution=SqlList::getListWithCrit("CronExecution", array("idle"=>"0"), 'id');
$inCronBlockFonctionCustom=true;
foreach (Cron::$listCronExecution as $key=>$cronExecution){
  if(is_numeric($cronExecution)){
    Cron::$listCronExecution[$key]=new CronExecution($cronExecution);
    $cronExecution=Cron::$listCronExecution[$key];
  }
  if ($cronExecution->fileExecuted) require_once $cronExecution->fileExecuted;
}

?>