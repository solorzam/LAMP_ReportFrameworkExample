<?php

/****************************************************************************************************************/
/*** SUBJECT LINES **********************************************************************************************/
/****************************************************************************************************************/
define("EMAIL_VALIDATION",			"Email Address Validation");
define("PASSWORD_RESET_REQUEST",	"Password Reset Request");
define("REF_NUMBER_LOOKUP_FAIL",	"Reference Number Lookup Failure");

/****************************************************************************************************************/
/*** SUCCESS MESSAGES *******************************************************************************************/
/****************************************************************************************************************/

/****************************************************************************************************************/
/*** ERROR TYPES ************************************************************************************************/
/****************************************************************************************************************/
define('ERR_TYPE_INACTIVITY',				"Inactivity");
define('ERR_TYPE_MALICIOUS',				"Malicious Activity");
define('ERR_TYPE_LOGIN',					"Login Attempt");
define('ERR_TYPE_VERIFICATION',				"Email Verification");
define('ERR_TYPE_PASSWORD_RESET',			"Password Reset");
define('ERR_TYPE_ISSUE_LOOKUP',				"Issues - Lookup");
define('ERR_TYPE_ISSUE_ADDCOMMENT',			"Issues - Adding Comment");
define('ERR_TYPE_ISSUE_CREATE',				"Issues - Create New Issue");
define('ERR_TYPE_SEARCH',					"Search");
define('ERR_TYPE_CONTACT_FORM',				"Contact Form Failure");
define('ERR_TYPE_REPORTS',					"Status Reports");
define('ERR_TYPE_AMNESTY',					"Amnesty Processing");
define('ERR_TYPE_DOCUMENT',					"Document");

/****************************************************************************************************************/
/*** ERROR MESSAGES *********************************************************************************************/
/****************************************************************************************************************/
define('ERR_GENERAL',						"Unknown or undefined error");
define('ERR_RELOGIN',						"Please relogin in. Thank you.");
define('ERR_INACTIVITY',					"You have been logged out due to 10 minutes of inactivity");
define('ERR_DB_SYNTAX',						"Database syntax error");
define('ERR_USER_EXISTS',					"User already exists");
define('ERR_USER_INVALID_ID',				"User ID does not exist");
define('ERR_USER_INVALID_ACTION_TYPE',		"Invalid action type");
define('ERR_USER_INVALID_ACTION_KEY',		"Invalid action key");

define('ERR_USER_EMAIL_EXISTS',				"Email address already exists");
define('ERR_SENDMAIL_FAILUE',				"There was an error sending your email");
define('ERR_EMAIL_FORMAT',					"The email you provided is not a correctly formatted email address");
define('ERR_GOOGLE_CAPTCHA',			    "Error your browser has failed the captcha test.");
define('ERR_ALL_FIELDS_REQUIRED',           "All fields are required");
define('ERR_NOACCESS_PROJECT_PLAN',			"You do not have access to the requested project and/or plan");
define('ERR_NOACCESS_EMPLOYEE',				"You do not have access to the requested employee information");

//login errors
define('ERR_LOGIN_FAILURE',					"The email &amp; password combination you entered failed. Please try again.");
define('ERR_LOGIN_NOEMAILPASSWORD',			"Please enter an email and a password");
define('ERR_LOGIN_NOEMAIL',					"You did not enter an email address");
define('ERR_LOGIN_NOPASSWORD',				"You did not enter a password");
define('ERR_LOGIN_NOTACTIVE',				"Your account has been disabled due to either inactivity or your verification reviews have all been completed");
define('ERR_LOGIN_NOPROJECTASSOC',			"You have not been associated with any projects yet. Please contact your project manager to get associated with a project.");
define('ERR_LOGIN_LOCKOUT',					"Your account has been locked due to excessive failed login attempts.");
define('ERR_INVALID_DATE',		            "Invalid date");
define('ERR_NOT_NUMERIC',		            "You entered text in a numeric only field.");
define('ERR_COOKIES_DISABLED',		        "This site requires cookies in order to track your session, please enable them.");
define('ERR_LOGIN_WRONG_PORTAL',			"The combination you entered maybe correct but you are using the wrong login portal, please contact your employer.");

//register errors
define('ERR_REGISTER_NONAME',				"You did not supply your name");
define('ERR_REGISTER_NOEMAIL',				"You did not supply an email");
define('ERR_REGISTER_NOPASSWORD',			"You did not supply a password");
define('ERR_REGISTER_NOCOMPANY',			"You did not supply a company name");
define('ERR_REGISTER_PASSWORDS_MISMATCH',	"Your passwords do not match");

//password reset errors
define('ERR_RESET_FAILURE',					"Unable to reset your password. Please try again later.");
define('ERR_RESET_REQUIRED_FIELDS',			"All fields are required");
define('ERR_RESET_NOEMAIL',					"You did not enter an email address");
define('ERR_RESET_INVALID_EMAIL',			"The email you provided could not be located. Make sure you are using the correct email address.");
define('ERR_RESET_EMAIL_DOES_NOT_EXIST',	"We do not have any records of the email you entered in our files");
define('ERR_RESET_MISSING_PASSWORD',		"You need to enter your new password in twice to successfully reset your password");
define('ERR_RESET_PASSWORDS_MISMATCH',		"Your passwords do not match");
define('ERR_RESET_PASSWORD_NOT_COMPLEX',	"The password you entered does not meet the complexity requirements.");
define('ERR_RESET_PASSWORDS_LENGTH',		"Your password must be at least 8 characters long");
define('ERR_RESET_NOEMAILVERIFICATIONKEY',	"You did not enter an email and a verification code provided in email.");
define('ERR_RESET_NOVERIFICATIONKEY',		"You did not enter a verification code provided in email..");
define('ERR_RESET_INVALIDKEYEMAIL',		    "You did not enter a valid key and email combination");

//issues errors
define('ERR_ISSUE_NO_TITLE',				"A title to your issue is required");
define('ERR_ISSUE_NO_COMMENT',				"A comment to your issue is required");
define('ERR_ISSUE_NO_TITLECOMMENT',			"Both the Title and Comment fields are required");
define('ERR_ISSUE_NO_ISSUE_EXISTS',			"The issue you attempted to lookup does not exist or you do not have enough access to view the issue");
define('ERR_ISSUE_NO_FAILURE',				"Could not create an issue at this time. Please try again later.");

//document errors
define('ERR_NO_DOC_FOUND',					"The document could not be found");

//report errors
define('ERR_REPORTS_ACCESS_OR_EXIST',		"You do not have access to the requested report or it does not exist");

//search errors
define('ERR_SEARCH_NO_QUERY',				"You did not enter in a search query. Please try again.");

//contact errors
define('ERR_CONTACT_REQUIREDFIELDS',		"All fields are required. Please try again.");
define('ERR_CONTACT_REASONREQUIRED',		"Please select a reason for contacting the support center");
define('ERR_CONTACT_MESSAGEREQUIRED',		"Please enter a message");

// error types
define('ERR_TYPE_MYAOS_LOGIN',				"Login Attempt");
define('ERR_TYPE_MYAOS_ADD_EMAIL',			"Account - Add Email");
define('ERR_TYPE_MYAOS_ADD_PASSWORD',		"Account - Add Password");
define('ERR_TYPE_MYAOS_REFIDLOOKUP_CONTACT',"Refernece ID Lookup - Contact");
define('ERR_TYPE_MYAOS_DOC_UPLOAD',			"Document Upload");

// document upload errors
define('ERR_MY_DOC_UPLOAD_NOFILE',                  "You did not select a file. Please select a PDF or JPG and click the 'Upload' button below. Thank you.");
define('ERR_MY_DOC_UPLOAD_MALICIOUS',               "That action is not allowed and is considered malicious activity. Please select a PDF or JPG and click the 'Upload' button below. Thank you.");
define('ERR_MY_DOC_UPLOAD_CORRUPT_FILE',            "The file you attempted to upload is corrupt and can not be upload at this time.");
define('ERR_MY_DOC_UPLOAD_FAILURE',                 "The file can not be upload at this time. Please try again later.");
define('ERR_MY_DOC_CONVERT_FAILURE',                "The file could not be converted to a PDF at this time. Please try again later.");
define('ERR_MY_DOC_VIRUS',                          "The file you attempted to upload contains a virus and can not be uploaded.");
define('ERR_MY_DOC_BAD_FORMAT',                     "The file you attempted to upload is not the correct format. Files uploaded need to be either a PDF or a JPG.");
define('ERR_MY_DOC_WRONG_FORMAT',                   "The file you attempted to upload is not the correct format.");
define('ERR_MY_DOC_TOO_LARGE',                      "The file you attempted to upload is over 5mb in size. Please reduce the files size before re-uploading.");
define('ERR_MY_DOC_PARTIAL_UPLOAD',                 "The file you attempted to upload failed to complete, please try again.");
define('ERR_MY_DOC_UPLOAD_BAD_FILENAME',            "The file you attempted to upload has special characters in the name. Please correct this and try again.");
define('ERR_MY_DOC_INTERNAL_ERROR',                 "An internal error occurred please try to upload the file again.");

// login errors
define('ERR_MYLOGIN_FAILURE',				"The reference number &amp; date of birth combination you entered failed. Please try again.");
define('ERR_MYLOGIN_NOENTRIES',				"Please enter in your reference number and date of birth");
define('ERR_MYLOGIN_NOREFERENCE',			"You did not enter a reference number");
define('ERR_MYLOGIN_INVALIDREFERENCE',		"The reference number you entered is not in the correct format. Your reference number will only have numeric characters.");
define('ERR_MYLOGIN_NODOB',					"You did not enter a date of birth");
define('ERR_MYLOGIN_PARTIALDOB',			"Please enter in your complete date of birth");
define('ERR_MYLOGIN_NOENTRIESCREATEPASS',	"All three fields are required. Please enter in a valid email followed by your password.");
define('ERR_MYLOGIN_BADEMAILFORMAT',		"The email address you entered is not in the correct format: username@domain.com");
define('ERR_MYLOGIN_PASSWORDDONOTMATCH',	"The passwords you provided do not match");
define('ERR_MYLOGIN_NOPASSWORD',			"Please enter in your account's password");
define('ERR_MYLOGIN_WRONGPASSWORD',			"The password you provided is incorrect");
define('ERR_MYLOGIN_NOVALIDATIONKEY',		"Could not find a validation key for the email you provided");
define('ERR_MYLOGIN_REFNUMBERNOTFOUND',		"Unable to find your reference number");
define('ERR_MYLOGIN_REFNUMBERNOTANUMBER',	"The reference number you entered is not a number");
define('ERR_MYLOGIN_RESET_NOEMAIL',			"Please enter in an email address");
define('ERR_MYLOGIN_RESET_WRONGEMAIL',		"The email you entered does not match any emails that we have on file. To reset your password please enter in the email you have set for your account.");
define('ERR_MYLOGIN_LOCKOUT',		 		"Your account has been locked due to excessive failed login attempts.");
define('ERR_MYLOGIN_IPLOCKOUT',		 		"Your IP address has been blocked due to excessive failed login attempts.");
define('ERR_NO_AOS_ACCESS',					"You do not have access to this portal");
define('ERR_CLOSED',					    "Audit Closed");
define('ERR_DEP_NOT_UNIQUE',			    "The dependent's first name cannot match your last name.");


// add email errors
define('ERR_MYLOGIN_ADDEMAIL_FAILURE',		"Could not add your email at this time. Please try again later.");
define('ERR_MYLOGIN_ADDEMAIL_EMPTY',		"Please enter in your email address twice");
define('ERR_MYLOGIN_ADDEMAIL_BAD_FORMAT',	"The email address you entered is not the in the correct format");
define('ERR_MYLOGIN_ADDEMAIL_DONT_MATCH',	"The email addresses you entered do not match");
define('ERR_MYLOGIN_ADDEMAIL_KEY_ERROR',	"Could not add verification key at this time");
define('ERR_MYLOGIN_ADDEMAIL_VALIDATION',	"Could not find your email validation key");

// add sms & verification errors
define('ERR_SMS_PHONE_NOT_NUMERIC',     "Invalid Phone Number Entered. Phone number must be numeric and ten digits in length.");
define('ERR_SMSPHONE_INVALID',              "Invalid Phone Number Entered. Phone number must be ten digits in length.");
define('ERR_SMSKEY_INVALID',                "Incorrect validation key entered. Please try again.");

// add password errors
define('ERR_MYLOGIN_ADDPASSWORD_FAILURE',	"Could not add a password at this time. Please try again later.");
define('ERR_MYLOGIN_ADDPASSWORD_EMPTY',		"Please enter a password");
define('ERR_MYLOGIN_ADDPASSWORD_DONT_MATCH',"The passwords you entered do not match");
define('ERR_MYLOGIN_ADDPASSWORD_BAD_FORMAT',"The password you entered is not the in the correct format");

// reference id lookup errors
define('ERR_MYLOGIN_REFIDLOOKUP_NOENTRIES',	"All fields are required");
define('ERR_MYLOGIN_REFIDLOOKUP_EMAILFORMAT',"The email you supplied is not in the correct format");

// amnesty processing errors
define('ERR_AMNESTY_NO_DEPS_DATE_SELECTED',	"You selected some or all of your dependents to be processed during amnesty but you did not select a <u>Date of Previous Eligibility</u>. Please select a date and submit.");
define('ERR_AMNESTY_AUTHORIZATION',             "You did not select the authorization selection. You must select this to give HMS the authorization to process your dependents during amnesty.");

define('ERR_FORM_TOKEN',                    "Form error, if you have two tabs open please close one and try again.");

// account update
define('ERR_ADDRESS_INVALID',               "A valid address is required");
define('ERR_PHONE_INVALID',                 "Invalid phone number");