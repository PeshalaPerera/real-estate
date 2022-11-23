<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ManageapplicationController
 *
 * @author Prasanna
 */
class ManageapplicationController extends Controller
{

    private $db;
    private $mid;
    private $fullURL;
    private $fullURLfront;
    private $API_URL;
    private $API_FULL_URL;
    private $caseManagerURL;
    private $TERM_OF_LOAN;
    private $TERM_OF_LOAN_ALUE;

    function __construct()
    {
        $this->db = self::$_db;
        $this->mid = self::$_mid;
        $this->fullURL = self::$_fullURL;
        $this->fullURLfront = self::$_fullURLfront;
        $this->API_URL = self::$_API_URL;
        $this->API_FULL_URL = self::$_API_FULL_URL;
        $this->caseManagerURL = self::$_caseManagerURL;
        $this->TERM_OF_LOAN = self::$_TERM_OF_LOAN;
        $this->TERM_OF_LOAN_ALUE = self::$_TERM_OF_LOAN_ALUE;

        include_once("./models/ApplicationModel.php");
        include_once("./models/ApplicationLogModel.php");
        include_once("./models/DirectorModel.php");
        include_once("./models/ReferenceModel.php");
        include_once("./models/SupplierModel.php");
        include_once("./models/GuarantorModel.php");
        include_once("./models/CompanymasterotherModel.php");
        include_once("./models/CompanymasterotherdetailModel.php");
        include_once("./models/CompanyModel.php");
        include_once("./models/CountryModel.php");
        include_once("./models/AppstatusModel.php");
        include_once("./models/Dp3reasoncodeModel.php");
        include_once("./models/DP3bureaureportModel.php");
        include_once("./models/OwnertransferModel.php");
        include_once("./models/DP3decisionoverideModel.php");
        include_once("./models/CompanyuserModel.php");
        include_once("./models/MastersettingModel.php");
        include_once("./models/WitnessModel.php");
        include_once("./models/AttachmentModel.php");
        include_once("./models/NotesModel.php");
        include_once("./models/ProcessTaskModel.php");
        include_once("./models/AppstatusModel.php");
        include_once("./models/AttachmentStatusModel.php");
        include_once("./models/AdminTaskModel.php");
        include_once("./models/PpsrModel.php");
        include_once("./models/SmartSignatureModel.php");
        include_once("./models/UserPriviledgeModel.php");
        include_once("./models/DataElementModel.php");
        include_once("./models/CompanyApiModel.php");
        include_once("./models/CmSetupScreenModel.php");
        include_once("./models/CmSetupScreenInfoModel.php");
        include_once("./models/AppEmailLogModel.php");
        include_once("./models/NATcheckModel.php");
        include_once("./models/QualityChecksModel.php");
        include_once("./models/CustomerBranchModel.php");
        include_once("./models/ThirdPartyGuaranteeModel.php");
        include_once("./models/ComCountryListModel.php");
        include_once("./models/WatchlistModel.php");
        include_once("./models/ApplicationWatchlistModel.php");
        include_once("./models/ApplicationWatchlistInfoModel.php");

        //include controller
        include_once("ApplicationController.php");
        include_once("WSDLController.php");
        include_once("UserPriviledgeController.php");
        include_once('./classes/PHPMailerAutoload.php');

        // include encrypt
        include_once("./classes/en_de.php");
        include_once("./classes/Pagination.php");
    }

    public function getApplicationDp3DecisioningOptions()
    {
        $vurl = check_submit_var($_POST['vurl'], 'V', 0, 0, 1, '');
        $applicationController = new ApplicationController($this->db);

        $application_info = $applicationController->getApplicationByVURL($vurl);

        $response = ['status' => 'error'];

        if ($application_info) {

            $dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);
            $dp3reason_code = $dp3reasoncodeModel->getDP3reasonByAppID($application_info->IID);

            $R_187 = false;

            if (!empty($dp3reason_code)) {
                foreach ($dp3reason_code as $dp3reason) {
                    if ($dp3reason->VREASON_CODE == 'R_187') {
                        $R_187 = true;
                    }
                }
            }

            $appstatusModel = new AppstatusModel($this->db);
            $status_info = $appstatusModel->getStatusByAppID($application_info->IID);

            $dp3_decisioning_tasks = [];
            if (!$R_187) {
                $dp3_decisioning_tasks = $this->getDecisioningTasks($application_info, $status_info);
            }

            $data_decisioningtasks = [
                'application_info' => $application_info,
                'dp3_decisioning_tasks' => $dp3_decisioning_tasks
            ];

            $view_decisioningtasks = 'views/manage_application/sub_manage_application/decisioningtasks';

            $res_decisioningtasks = $this->loadHtmlView($view_decisioningtasks, $data_decisioningtasks);
            if (!empty($res_decisioningtasks)) {
                $response['decisioningTasksHtml'] = $res_decisioningtasks;
                $response['status'] = 'success';
            }
        }
        echo json_encode($response);
    }

    public function getApplicationDp3ReasonCodes()
    {
        $vurl = check_submit_var($_POST['vurl'], 'V', 0, 0, 1, '');
        $applicationController = new ApplicationController($this->db);

        $application_info = $applicationController->getApplicationByVURL($vurl);

        $response = ['status' => 'error'];

        if ($application_info) {

            $companyModel = new CompanyModel($this->db);
            $countryModel = new CountryModel($this->db);
            $appstatusModel = new AppstatusModel($this->db);
            $dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);

            $company_info = $companyModel->getCompanyByID($application_info->ICOMPANY_ID);
            $country_info = $countryModel->getCountryByID($company_info->ICOUNTRY_ID);
            $status_info = $appstatusModel->getStatusByAppID($application_info->IID);
            $dp3reason_code = $dp3reasoncodeModel->getDP3reasonByAppID($application_info->IID);

            $R_187 = false;

            if (!empty($dp3reason_code)) {
                foreach ($dp3reason_code as $dp3reason) {
                    if ($dp3reason->VREASON_CODE == 'R_187') {
                        $R_187 = true;
                    }
                }
            }

            if (!$R_187) {
                if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY' && $country_info->VCODE == 'AU') {
                    $res_retrieve_response = $this->updateDP3DecisionsComAU($application_info, $dp3reason_code, $status_info);
                    if (!empty($res_retrieve_response)) {
                        $response['esisResponse'] = $res_retrieve_response['esisResponse'];
                        if (isset($res_retrieve_response['errorDetails']) && !empty($res_retrieve_response['errorDetails'])) {
                            if (count($res_retrieve_response['errorDetails']) > 1) {
                                $response['errorDetails'] = $res_retrieve_response['errorDetails'];
                            } else {
                                $response['errorDetails'][] = $res_retrieve_response['errorDetails'];
                            }
                        }
                        if (isset($res_retrieve_response['alertDetails']) && !empty($res_retrieve_response['alertDetails'])) {
                            if (count($res_retrieve_response['alertDetails']) > 1) {
                                $response['alertDetails'] = $res_retrieve_response['alertDetails'];
                            } else {
                                $response['alertDetails'][] = $res_retrieve_response['alertDetails'];
                            }
                        }
                    }
                } else if (strtoupper($application_info->VAPPLICATION_TYPE) == 'INDIVIDUAL' && $country_info->VCODE == 'AU') {
                    $res_retrieve_response = $this->updateDP3DecisionsIndAU($application_info, $dp3reason_code, $status_info);
                    if (!empty($res_retrieve_response)) {
                        $response['esisResponse'] = $res_retrieve_response['esisResponse'];
                        if (isset($res_retrieve_response['errorDetails']) && !empty($res_retrieve_response['errorDetails'])) {
                            if (count($res_retrieve_response['errorDetails']) > 1) {
                                $response['errorDetails'] = $res_retrieve_response['errorDetails'];
                            } else {
                                $response['errorDetails'][] = $res_retrieve_response['errorDetails'];
                            }
                        }
                        if (isset($res_retrieve_response['alertDetails']) && !empty($res_retrieve_response['alertDetails'])) {
                            if (count($res_retrieve_response['alertDetails']) > 1) {
                                $response['alertDetails'] = $res_retrieve_response['alertDetails'];
                            } else {
                                $response['alertDetails'][] = $res_retrieve_response['alertDetails'];
                            }
                        }
                    }
                } else if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY' && $country_info->VCODE == 'NZ') {
                    $res_retrieve_response = $this->updateDP3DecisionsComNZ($application_info, $dp3reason_code, $status_info);
                    if (!empty($res_retrieve_response)) {
                        if (isset($res_retrieve_response['errorDetails']) && !empty($res_retrieve_response['errorDetails'])) {
                            if (count($res_retrieve_response['errorDetails']) > 1) {
                                $response['errorDetails'] = $res_retrieve_response['errorDetails'];
                            } else {
                                $response['errorDetails'][] = $res_retrieve_response['errorDetails'];
                            }
                        }
                        if (isset($res_retrieve_response['alertDetails']) && !empty($res_retrieve_response['alertDetails'])) {
                            if (count($res_retrieve_response['alertDetails']) > 1) {
                                $response['alertDetails'] = $res_retrieve_response['alertDetails'];
                            } else {
                                $response['alertDetails'][] = $res_retrieve_response['alertDetails'];
                            }
                        }
                    }
                } else if (strtoupper($application_info->VAPPLICATION_TYPE) == 'INDIVIDUAL' && $country_info->VCODE == 'NZ') {
                    $res_retrieve_response = $this->updateDP3DecisionsIndNZ($application_info, $dp3reason_code, $status_info);
                    if (!empty($res_retrieve_response)) {
                        if (isset($res_retrieve_response['errorDetails']) && !empty($res_retrieve_response['errorDetails'])) {
                            if (count($res_retrieve_response['errorDetails']) > 1) {
                                $response['errorDetails'] = $res_retrieve_response['errorDetails'];
                            } else {
                                $response['errorDetails'][] = $res_retrieve_response['errorDetails'];
                            }
                        }
                        if (isset($res_retrieve_response['alertDetails']) && !empty($res_retrieve_response['alertDetails'])) {
                            if (count($res_retrieve_response['alertDetails']) > 1) {
                                $response['alertDetails'] = $res_retrieve_response['alertDetails'];
                            } else {
                                $response['alertDetails'][] = $res_retrieve_response['alertDetails'];
                            }
                        }
                    }
                }

                $status_info_new = $appstatusModel->getStatusByAppID($application_info->IID);
                $this->updateNewDP3Decisions($application_info, $status_info_new);
            }

            $dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);

            $response['dp3reasonCodes'] = $dp3reasoncodeModel->getDP3reasonByAppID($application_info->IID);
            $response['status'] = 'success';
        }
        echo json_encode($response);
    }

    public function downloadBureauReportFromDP3()
    {

        $vurl = check_submit_var($_POST['vurl'], 'V', 0, 0, 1, '');
        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($vurl);

        $CompanyModel = new CompanyModel($this->db);
        $DP3bureaureportModel = new DP3bureaureportModel($this->db);

        $response_data = array('status' => 'error');

        if ($application_info) {

            $dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);
            $dp3reason_code = $dp3reasoncodeModel->getDP3reasonByAppID($application_info->IID);

            $R_187 = false;

            if (!empty($dp3reason_code)) {
                foreach ($dp3reason_code as $dp3reason) {
                    if ($dp3reason->VREASON_CODE == 'R_187') {
                        $R_187 = true;
                    }
                }
            }

            if (!$R_187) {
                $company_info = $CompanyModel->getComContryByComID($application_info->ICOMPANY_ID);
                $dp3bureaureport_info = $DP3bureaureportModel->getDP3bureaureportByAppID($application_info->IID);

                if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY' && $company_info->COUNTRY_CODE == 'AU') {
                    $bureau_report_pdf_files = $DP3bureaureportModel->getAllDP3bureauReportFileByAppID($application_info->IID);
                    if (empty($bureau_report_pdf_files)) {
                        $this->dp3BureauReportNZCom($application_info, $dp3bureaureport_info);
                    }
                } else if (strtoupper($application_info->VAPPLICATION_TYPE) == 'INDIVIDUAL' && $company_info->COUNTRY_CODE == 'AU') {
                    $DirectorModel = new DirectorModel($this->db);
                    $directors_info = $DirectorModel->getAllDirectorsByApplicationIID($application_info->IID);
                    $bureau_report_pdf_files = $DP3bureaureportModel->getAllDP3bureauReportFileByAppID($application_info->IID);
                    if (count($directors_info) > count($bureau_report_pdf_files)) {
                        $this->dp3BureauReportNZInd($application_info, $dp3bureaureport_info);
                    }
                } else if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY' && $company_info->COUNTRY_CODE == 'NZ') {
                    $bureau_report_pdf_files = $DP3bureaureportModel->getAllDP3bureauReportFileByAppID($application_info->IID);
                    if (empty($bureau_report_pdf_files)) {
                        $this->dp3BureauReportNZCom($application_info, $dp3bureaureport_info);
                    }
                } else if (strtoupper($application_info->VAPPLICATION_TYPE) == 'INDIVIDUAL' && $company_info->COUNTRY_CODE == 'NZ') {
                    $DirectorModel = new DirectorModel($this->db);
                    $directors_info = $DirectorModel->getAllDirectorsByApplicationIID($application_info->IID);
                    $bureau_report_pdf_files = $DP3bureaureportModel->getAllDP3bureauReportFileByAppID($application_info->IID);
                    if (count($directors_info) > count($bureau_report_pdf_files)) {
                        $this->dp3BureauReportNZInd($application_info, $dp3bureaureport_info);
                    }
                }
            }
        }

        $response_data['dp3bureauReports'] = $DP3bureaureportModel->getDP3bureaureportByAppID($application_info->IID);

        echo json_encode($response_data);
    }

    /*2019-07-22*/
    public function updateSmartSignatureStatus()
    {

        $vurl = check_submit_var($_POST['app_vurl'], 'V', 0, 0, 1, '');

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($vurl);

        $html = "";
        $status = "";

        if ($application_info) {

            $status = "success";

            $smartSignatureModel = new SmartSignatureModel($this->db);

            $all_res_count = $smartSignatureModel->getApplicantSignatureCount($application_info->IID, '', '');
            $all_received_count = $smartSignatureModel->getApplicantSignatureCount($application_info->IID, 1, 1);

            $pending_sign_apps_count = $smartSignatureModel->getPendingApplicantSignatureCount($application_info->IID, "");
            $pending_verify_apps_count = $smartSignatureModel->getPendingApplicantSignatureCount($application_info->IID, 1);

            $not_require_apps_count = $smartSignatureModel->getNotRQMSapplicantSignatureCount($application_info->IID, 4);
            $manually_sign_apps_count = $smartSignatureModel->getNotRQMSapplicantSignatureCount($application_info->IID, 5);

            $AppstatusModel = new AppstatusModel($this->db);

            $status_info = $AppstatusModel->getStatusByAppID($application_info->IID);

            if ($pending_verify_apps_count->rec_count > 0) {
                if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'PENDING VERIFICATION') {
                    $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'PENDING VERIFICATION')), $status_info->IID);
                }
            } else if (($all_received_count->rec_count + $manually_sign_apps_count->rec_count) > 0) {
                if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'COMPLETED') {
                    $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'COMPLETED')), $status_info->IID);
                }
            } else if ($not_require_apps_count->rec_count > 0 && $not_require_apps_count->rec_count == $all_res_count->rec_count) {
                if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'NOT REQUIRED') {
                    $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'NOT REQUIRED')), $status_info->IID);
                }
            } else if ($pending_verify_apps_count->rec_count == 0 && ($all_received_count->rec_count + $manually_sign_apps_count->rec_count) == 0 && $not_require_apps_count->rec_count < $all_res_count->rec_count && $pending_sign_apps_count->rec_count > 0) {
                if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'SIGNATURE PENDING') {
                    $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'SIGNATURE PENDING')), $status_info->IID);
                }
            }

            $status_info_new = $AppstatusModel->getStatusByAppID($application_info->IID);

            if (strtoupper($status_info_new->VSMART_SIGNATURE_STATUS) == 'PENDING VERIFICATION') {
                $tot_pending_verify_apps_count = 0;
                $tot_all_res_count = 0;
                if ($pending_verify_apps_count->rec_count) {
                    $tot_pending_verify_apps_count = $pending_verify_apps_count->rec_count;
                }
                if ($all_res_count->rec_count) {
                    $tot_all_res_count = $all_res_count->rec_count;
                }
                $html .= '<span class="badge bg-green" style="width:134px;">PENDING VERIFICATION</span>';
                $html .= '<span class="badge bg-green" style="width:38px; margin-left: 10px;">' . $tot_pending_verify_apps_count . '/' . $tot_all_res_count . '</span>';
            } else if (strtoupper($status_info_new->VSMART_SIGNATURE_STATUS) == 'SIGNATURE PENDING') {
                $tot_pending_sign_apps_count = 0;
                $tot_all_res_count = 0;
                /*if($pending_sign_apps_count->rec_count){
					$tot_pending_sign_apps_count = $pending_sign_apps_count->rec_count;
				}*/
                if ($all_res_count->rec_count) {
                    $tot_all_res_count = $all_res_count->rec_count;
                }
                $html .= '<span class="badge bg-yellow-gold" style="width:134px;">SIGNATURE PENDING</span>';
                $html .= '<span class="badge bg-yellow-gold" style="width:38px; margin-left: 10px;">' . $tot_pending_sign_apps_count . '/' . $tot_all_res_count . '</span>';
            } else if (strtoupper($status_info_new->VSMART_SIGNATURE_STATUS) == 'COMPLETED') {
                $tot_approved_sign_apps_count = 0;
                $tot_manually_sign_apps_count = 0;
                $tot_all_res_count = 0;
                if ($all_received_count->rec_count) {
                    $tot_approved_sign_apps_count = $all_received_count->rec_count;
                }
                if ($manually_sign_apps_count->rec_count) {
                    $tot_manually_sign_apps_count = $manually_sign_apps_count->rec_count;
                }
                if ($all_res_count->rec_count) {
                    $tot_all_res_count = $all_res_count->rec_count;
                }
                $html .= '<span class="badge bg-green-seagreen" style="width:134px;">COMPLETED</span>';
                $html .= '<span class="badge bg-green-seagreen" style="width:38px; margin-left: 10px;">' . ($tot_approved_sign_apps_count + $tot_manually_sign_apps_count) . '/' . $tot_all_res_count . '</span>';
            } else if (strtoupper($status_info_new->VSMART_SIGNATURE_STATUS) == 'NOT REQUIRED') {
                $tot_not_require_apps_count = 0;
                $tot_all_res_count = 0;
                if ($all_res_count->rec_count) {
                    $tot_all_res_count = $all_res_count->rec_count;
                }
                $html .= '<span class="badge bg-gray" style="width:134px;">NOT REQUIRED</span>';
                $html .= '<span class="badge bg-gray" style="width:38px; margin-left: 10px;">' . ($tot_not_require_apps_count) . '/' . $tot_all_res_count . '</span>';
            }
        } else {
            $status = "error";
        }

        $data_res = array(
            'status' => $status,
            'html' => $html
        );

        if ($status == 'success') {

            /*time zone*/
            $companyModel = new CompanyModel($this->db);
            $company_dtls = $companyModel->getAllCompanies($application_info->ICOMPANY_ID);

            $country_code = $company_dtls->COUNTRY_CODE;
            $time_zone = 'Australia/Melbourne';
            if ($country_code == "NZ") {
                $time_zone = 'Pacific/Auckland';
            }
            /*end of time zone*/

            /*notes*/
            $notesModel = new NotesModel($this->db);
            $casemanager_notes = $notesModel->getAllNotesByAppIdVcategory($application_info->IID, 'case_manager', 1);

            $new_notes_count = 0;

            foreach ($casemanager_notes as $cm_note_r) {
                if (empty($cm_note_r->IREAD) || $cm_note_r->IREAD == 0) {
                    $new_notes_count++;
                }
            }

            $data_notes = array(
                'casemanager_notes' => $casemanager_notes,
                'time_zone' => $time_zone
            );

            $view_notes = 'views/manage_application/sub_manage_application/notes';

            $res_notes = $this->loadHtmlView($view_notes, $data_notes);
            if (!empty($res_notes)) {
                $data_res['res_notes_html'] = $res_notes;
                $data_res['res_new_notes_count'] = $new_notes_count;
            }
            /*end of notes*/
        }

        echo json_encode($data_res);
    }
    /*2019-07-22*/

    /* 2019-03-25 */
    public function runEmailCronJobManually()
    {

        $caseManagerURL = check_submit_var($_POST['caseManagerURL'], 'V', 0, 0, 1, '');

        $url = $caseManagerURL . '/cronjobs/send_app_email_cronjob.php';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        try {
            $response_auth = curl_exec($ch);
        } catch (Exception $ex) {
            $response_auth = "";
        }

        echo json_encode($response_auth);
    }

    public function updateQualityChecksList()
    {

        $res = array();

        $qualityChecksModel = new QualityChecksModel();

        $IAPPLICATION_VURL = check_submit_var($_POST["app_vurl"], 'V', 0, 0, 1, '');
        $ICORRECT_ENTITY = check_submit_var($_POST["ICORRECT_ENTITY"], 'V', 0, 0, 1, '');
        $ITRS_CHECKS = check_submit_var($_POST["ITRS_CHECKS"], 'V', 0, 0, 1, '');
        $IU_CREDIT_FILE = check_submit_var($_POST["IU_CREDIT_FILE"], 'V', 0, 0, 1, '');
        $IOVERALL_ACCURACY = check_submit_var($_POST["IOVERALL_ACCURACY"], 'V', 0, 0, 1, '');
        $INOTES_QUALITY = check_submit_var($_POST["INOTES_QUALITY"], 'V', 0, 0, 1, '');
        $TCOMMENTS = check_submit_var($_POST["TCOMMENTS"], 'V', 0, 0, 1, '');
        $ISAVE = check_submit_var($_POST["ISAVE"], 'V', 0, 0, 1, '');

        $ICORRECT_ENTITY_VAL = 0;
        $ITRS_CHECKS_VAL = 0;
        $IU_CREDIT_FILE_VAL = 0;
        $IOVERALL_ACCURACY_VAL = 0;
        $INOTES_QUALITY_VAL = 0;

        if (!empty($ICORRECT_ENTITY)) {
            $ICORRECT_ENTITY_VAL = 1;
        }
        if (!empty($ITRS_CHECKS)) {
            $ITRS_CHECKS_VAL = 1;
        }
        if (!empty($IU_CREDIT_FILE)) {
            $IU_CREDIT_FILE_VAL = 1;
        }
        if (!empty($IOVERALL_ACCURACY)) {
            $IOVERALL_ACCURACY_VAL = 1;
        }
        if (!empty($INOTES_QUALITY)) {
            $INOTES_QUALITY_VAL = 1;
        }

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($IAPPLICATION_VURL);

        $ownertransferModel = new OwnertransferModel();

        if (!empty($application_info)) {

            $app_owner_id = 0;
            $res_app_owner = $ownertransferModel->getOwnerTransferByAppID($application_info->IID);
            if (!empty($res_app_owner)) {
                $app_owner_id = $res_app_owner->ICURRENT_OWNER_ID;
            }

            $res_quality_checks_list = $qualityChecksModel->getQualityChecksByAppId($application_info->IID);

            if (!empty($res_quality_checks_list)) {

                /*save history before update*/
                $log_data_array = array();
                foreach ($res_quality_checks_list as $k => $v) {
                    if (!in_array($k, array('IID'))) {
                        array_push($log_data_array, array($k, $v));
                    } else {
                        array_push($log_data_array, array('IQC_ID', $v));
                    }
                }
                $qualityChecksModel->insertHistory($log_data_array);
                unset($log_data_array);
                /*save history before update*/

                $update_data = array(
                    array('ICORRECT_ENTITY', $ICORRECT_ENTITY_VAL),
                    array('ITRS_CHECKS', $ITRS_CHECKS_VAL),
                    array('IU_CREDIT_FILE', $IU_CREDIT_FILE_VAL),
                    array('IOVERALL_ACCURACY', $IOVERALL_ACCURACY_VAL),
                    array('INOTES_QUALITY', $INOTES_QUALITY_VAL),
                    array('TCOMMENTS', $TCOMMENTS),
                    array('IOWNER_ID', $app_owner_id),
                    array('IUPDATED_BY', $_SESSION['user_id']),
                    array('DUPDATED_AT', date('Y-m-d H:i:s'))
                );

                $res_update = $qualityChecksModel->update($update_data, array(array('IID', $res_quality_checks_list->IID)));

                if ($res_update) {
                    $res = array('status' => 'success', 'step_index' => 1);
                } else {
                    $res = array('status' => 'error');
                }
            } else {

                $ins_data = array(
                    array('IAPPLICATION_ID', $application_info->IID),
                    array('ICORRECT_ENTITY', $ICORRECT_ENTITY_VAL),
                    array('ITRS_CHECKS', $ITRS_CHECKS_VAL),
                    array('IU_CREDIT_FILE', $IU_CREDIT_FILE_VAL),
                    array('IOVERALL_ACCURACY', $IOVERALL_ACCURACY_VAL),
                    array('INOTES_QUALITY', $INOTES_QUALITY_VAL),
                    array('TCOMMENTS', $TCOMMENTS),
                    array('ITOTAL', 0),
                    array('ISTATUS', 0),
                    array('IPUBLISH', 1),
                    array('IADDED_BY', $_SESSION['user_id']),
                    array('DADDED_AT', date('Y-m-d H:i:s')),
                    array('IOWNER_ID', $app_owner_id),
                    array('VURL', getToken())
                );

                $res_ins = $qualityChecksModel->insert($ins_data);

                if ($res_ins) {
                    $res = array('status' => 'success', 'step_index' => 1);
                } else {
                    $res = array('status' => 'error');
                }
            }

            $res_new_quality_checks_list = $qualityChecksModel->getQualityChecksByAppId($application_info->IID);

            if (!empty($res_new_quality_checks_list)) {

                $count_quality_checks = 0;

                if (!empty($res_new_quality_checks_list->ICORRECT_ENTITY)) {
                    $count_quality_checks += $res_new_quality_checks_list->ICORRECT_ENTITY;
                }
                if (!empty($res_new_quality_checks_list->ITRS_CHECKS)) {
                    $count_quality_checks += $res_new_quality_checks_list->ITRS_CHECKS;
                }
                if (!empty($res_new_quality_checks_list->IU_CREDIT_FILE)) {
                    $count_quality_checks += $res_new_quality_checks_list->IU_CREDIT_FILE;
                }
                if (!empty($res_new_quality_checks_list->IOVERALL_ACCURACY)) {
                    $count_quality_checks += $res_new_quality_checks_list->IOVERALL_ACCURACY;
                }
                if (!empty($res_new_quality_checks_list->INOTES_QUALITY)) {
                    $count_quality_checks += $res_new_quality_checks_list->INOTES_QUALITY;
                }

                if ($count_quality_checks > 0) {

                    $update_qcl = array(array('ITOTAL', $count_quality_checks));

                    if ($ISAVE) {
                        array_push($update_qcl, array('IUPDATED_BY', $_SESSION['user_id']));
                        array_push($update_qcl, array('ISTATUS', 1));
                    }

                    $qualityChecksModel->update($update_qcl, array(array('IID', $res_new_quality_checks_list->IID)));

                    $res = array('status' => 'success', 'step_index' => 200);

                    $notesModel = new NotesModel($this->db);

                    $data_arr = array();

                    $companyModel = new CompanyModel($this->db);
                    $company_dtls = $companyModel->getAllCompanies($application_info->ICOMPANY_ID);

                    $country_code = $company_dtls->COUNTRY_CODE;
                    $time_zone = 'Australia/Melbourne';
                    if ($country_code == "NZ") {
                        $time_zone = 'Pacific/Auckland';
                    }

                    $converted_datetime = get_country_datetime($time_zone, date('Y-m-d H:i:s'), 'Y-m-d h:i:s A');

                    $note_text = 'Quality Checks Completed by ' . $_SESSION['user_name'] . ' on ' . $converted_datetime;

                    array_push($data_arr, array('VURL', getToken()));
                    array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
                    array_push($data_arr, array('VCATEGORY', 'case_manager'));
                    array_push($data_arr, array('TNOTE', $note_text));
                    array_push($data_arr, array('VSECTION', 'process_tasks'));
                    array_push($data_arr, array('IADDED_BY', $_SESSION['user_id']));
                    array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
                    array_push($data_arr, array('IPUBLISH', 1));

                    $res_note = $notesModel->insert($data_arr);

                    if ($res_note) {
                        $casemanager_notes = $notesModel->getAllNotesByAppIdVcategory($application_info->IID, 'case_manager', 1);

                        $new_notes_count = 0;

                        foreach ($casemanager_notes as $cm_note_r) {
                            if (empty($cm_note_r->IREAD) || $cm_note_r->IREAD == 0) {
                                $new_notes_count++;
                            }
                        }

                        $data_notes = array(
                            'casemanager_notes' => $casemanager_notes,
                            'time_zone' => $time_zone
                        );

                        $view_notes = 'views/manage_application/sub_manage_application/notes';

                        $res_notes = $this->loadHtmlView($view_notes, $data_notes);
                        if (!empty($res_notes)) {
                            $res['res_notes_html'] = $res_notes;
                            $res['res_new_notes_count'] = $new_notes_count;
                        }
                    }
                }
            }
        } else {
            $res = array('status' => 'error');
        }

        echo json_encode($res);
    }

    /* 2019-03-25 */

    public function updateNatCheckList()
    {

        $res = array();

        $NATcheckModel = new NATcheckModel();
        $appstatusModel = new AppstatusModel();

        $IAPPLICATION_VURL = check_submit_var($_POST["app_vurl"], 'V', 0, 0, 1, '');
        $ICHECK_ABN = check_submit_var($_POST["ICHECK_ABN"], 'V', 0, 0, 1, '');
        $ICHECK_EXT_ACC = check_submit_var($_POST["ICHECK_EXT_ACC"], 'V', 0, 0, 1, '');
        $ICHECK_REV_CRD = check_submit_var($_POST["ICHECK_REV_CRD"], 'V', 0, 0, 1, '');
        $ICHECK_TRS_ACC = check_submit_var($_POST["ICHECK_TRS_ACC"], 'V', 0, 0, 1, '');
        $ICHECK_ADD_NOTES = check_submit_var($_POST["ICHECK_ADD_NOTES"], 'V', 0, 0, 1, '');
        $ICHECK_COMPLETED = check_submit_var($_POST["ICHECK_COMPLETED"], 'V', 0, 0, 1, '');
        $nat_account_no = check_submit_var($_POST["nat_account_no"], 'V', 0, 0, 1, '');

        $ICHECK_ABN_VAL = 0;
        $ICHECK_EXT_ACC_VAL = 0;
        $ICHECK_REV_CRD_VAL = 0;
        $ICHECK_TRS_ACC_VAL = 0;
        $ICHECK_ADD_NOTES_VAL = 0;
        $ICHECK_COMPLETED_VAL = 0;
        $nat_is_empty = 0;

        if (!empty($ICHECK_ABN)) {
            $ICHECK_ABN_VAL = 1;
        }
        if (!empty($ICHECK_EXT_ACC)) {
            $ICHECK_EXT_ACC_VAL = 1;
        }
        if (!empty($ICHECK_REV_CRD)) {
            $ICHECK_REV_CRD_VAL = 1;
        }
        if (!empty($ICHECK_TRS_ACC)) {
            $ICHECK_TRS_ACC_VAL = 1;
        }
        if (!empty($ICHECK_ADD_NOTES)) {
            $ICHECK_ADD_NOTES_VAL = 1;
        }
        if (!empty($ICHECK_COMPLETED)) {
            $ICHECK_COMPLETED_VAL = 1;
        }
        if (!empty($nat_account_no)) {
            $nat_is_empty = 1;
        }

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($IAPPLICATION_VURL);

        if (!empty($application_info)) {

            $res_nat_chk_list = $NATcheckModel->getNATresByAppId($application_info->IID);

            if (!empty($res_nat_chk_list)) {

                $update_data = array(
                    array('ICHECK_ABN', $ICHECK_ABN_VAL),
                    array('ICHECK_EXT_ACC', $ICHECK_EXT_ACC_VAL),
                    array('ICHECK_REV_CRD', $ICHECK_REV_CRD_VAL),
                    array('ICHECK_TRS_ACC', $ICHECK_TRS_ACC_VAL),
                    array('ICHECK_ADD_NOTES', $ICHECK_ADD_NOTES_VAL),
                    array('ICHECK_COMPLETED', $ICHECK_COMPLETED_VAL),
                    array('VACC_NO', $nat_account_no),
                    array('IUPDATED_BY', $_SESSION['user_id']),
                    array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                );

                $res_update = $NATcheckModel->update($update_data, array(array('IID', $res_nat_chk_list->IID)));

                if ($res_update) {
                    $res = array('status' => 'success', 'step_index' => 1);
                } else {
                    $res = array('status' => 'error');
                }
            } else {

                $ins_data = array(
                    array('IAPPLICATION_ID', $application_info->IID),
                    array('ICHECK_ABN', $ICHECK_ABN_VAL),
                    array('ICHECK_EXT_ACC', $ICHECK_EXT_ACC_VAL),
                    array('ICHECK_REV_CRD', $ICHECK_REV_CRD_VAL),
                    array('ICHECK_TRS_ACC', $ICHECK_TRS_ACC_VAL),
                    array('ICHECK_ADD_NOTES', $ICHECK_ADD_NOTES_VAL),
                    array('ICHECK_COMPLETED', $ICHECK_COMPLETED_VAL),
                    array('VACC_NO', $nat_account_no),
                    array('IPUBLISH', 1),
                    array('IADDED_BY', $_SESSION['user_id']),
                    array('DADDED_DATETIME', date('Y-m-d H:i:s')),
                    array('VURL', getToken())
                );

                $res_ins = $NATcheckModel->insert($ins_data);

                if ($res_ins) {
                    $res = array('status' => 'success', 'step_index' => 1);
                } else {
                    $res = array('status' => 'error');
                }
            }

            /* check NAT status */
            $new_nat_chk_list = $NATcheckModel->getNATresByAppId($application_info->IID);
            $status_info = $appstatusModel->getStatusByAppID($application_info->IID);

            if (!empty($new_nat_chk_list) && !empty($status_info)) {

                $ICHECK_ABN_IDX = 0;
                $ICHECK_EXT_ACC_IDX = 0;
                $ICHECK_REV_CRD_IDX = 0;
                $ICHECK_TRS_ACC_IDX = 0;
                $ICHECK_ADD_NOTES_IDX = 0;
                $ICHECK_COMPLETED_IDX = 0;
                $nat_is_empty = 0;

                if (!empty($new_nat_chk_list->ICHECK_ABN)) {
                    $ICHECK_ABN_IDX = 1;
                }
                if (!empty($new_nat_chk_list->ICHECK_EXT_ACC)) {
                    $ICHECK_EXT_ACC_IDX = 1;
                }
                if (!empty($new_nat_chk_list->ICHECK_REV_CRD)) {
                    $ICHECK_REV_CRD_IDX = 1;
                }
                if (!empty($new_nat_chk_list->ICHECK_TRS_ACC)) {
                    $ICHECK_TRS_ACC_IDX = 1;
                }
                if (!empty($new_nat_chk_list->ICHECK_ADD_NOTES)) {
                    $ICHECK_ADD_NOTES_IDX = 1;
                }
                if (!empty($new_nat_chk_list->ICHECK_COMPLETED)) {
                    $ICHECK_COMPLETED_IDX = 1;
                }
                if (!empty($nat_account_no)) {
                    $nat_is_empty = 1;
                }

                if (($ICHECK_ABN_IDX + $ICHECK_EXT_ACC_IDX + $ICHECK_REV_CRD_IDX + $ICHECK_TRS_ACC_IDX + $ICHECK_ADD_NOTES_IDX + $ICHECK_COMPLETED_IDX + $nat_is_empty) === 6) { //$ICHECK_COMPLETED_IDX=0

                    $update_app_status_data = array(
                        array('VNAT_CHECK_STATUS', "COMPLETED"),
                        array('DNAT_COMPLETED_DATETIME', date('Y-m-d H:i:s')),
                        array('IUPDATED_BY', $_SESSION['user_id']),
                        array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                    );

                    $res_update = $appstatusModel->update($update_app_status_data, $status_info->IID);

                    if ($res_update) {

                        $res = array('status' => 'success', 'step_index' => 200);

                        /*add process tasks completed note*/
                        $notesModel = new NotesModel($this->db);

                        $data_arr = array();

                        $companyModel = new CompanyModel($this->db);
                        $company_dtls = $companyModel->getAllCompanies($application_info->ICOMPANY_ID);

                        $country_code = $company_dtls->COUNTRY_CODE;
                        $time_zone = 'Australia/Melbourne';
                        if ($country_code == "NZ") {
                            $time_zone = 'Pacific/Auckland';
                        }

                        $converted_datetime = get_country_datetime($time_zone, date('Y-m-d H:i:s'), 'Y-m-d h:i:s A');

                        $note_text = 'Process task Completed by ' . $_SESSION['user_name'] . ' on ' . $converted_datetime;

                        array_push($data_arr, array('VURL', getToken()));
                        array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
                        array_push($data_arr, array('VCATEGORY', 'case_manager'));
                        array_push($data_arr, array('TNOTE', $note_text));
                        array_push($data_arr, array('VSECTION', 'process_tasks'));
                        array_push($data_arr, array('IADDED_BY', $_SESSION['user_id']));
                        array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
                        array_push($data_arr, array('IPUBLISH', 1));

                        $res_note = $notesModel->insert($data_arr);
                        /*end of add process tasks completed note*/

                        /*notes*/
                        if ($res_note) {
                            $casemanager_notes = $notesModel->getAllNotesByAppIdVcategory($application_info->IID, 'case_manager', 1);

                            $new_notes_count = 0;

                            foreach ($casemanager_notes as $cm_note_r) {
                                if (empty($cm_note_r->IREAD) || $cm_note_r->IREAD == 0) {
                                    $new_notes_count++;
                                }
                            }

                            $data_notes = array(
                                'casemanager_notes' => $casemanager_notes,
                                'time_zone' => $time_zone
                            );

                            $view_notes = 'views/manage_application/sub_manage_application/notes';

                            $res_notes = $this->loadHtmlView($view_notes, $data_notes);
                            if (!empty($res_notes)) {
                                $res['res_notes_html'] = $res_notes;
                                $res['res_new_notes_count'] = $new_notes_count;
                            }
                        }
                        /*end of notes*/
                    }
                } else {

                    $update_app_status_data = array(
                        array('VNAT_CHECK_STATUS', "PENDING"),
                        array('DNAT_COMPLETED_DATETIME', NULL),
                        array('IUPDATED_BY', $_SESSION['user_id']),
                        array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                    );

                    $res_update = $appstatusModel->update($update_app_status_data, $status_info->IID);

                    if ($res_update) {
                        $res = array('status' => 'success', 'step_index' => 1);
                    }
                }
            }
            /* end of check NAT status */
        } else {
            $res = array('status' => 'error');
        }

        echo json_encode($res);
    }

    /**
     *
     * @param type $vurl
     * @param type $caseManagerURL
     */
    public function generatePDF_HTML($vurl, $caseManagerURL)
    {

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($vurl);

        if ($application_info) {

            $applicationModel = new ApplicationModel($this->db);
            $directorModel = new DirectorModel($this->db);
            $guarantorModel = new GuarantorModel($this->db);
            $referenceModel = new ReferenceModel($this->db);
            $supplierModel = new SupplierModel($this->db);
            $appstatusModel = new AppstatusModel($this->db);
            //$appFillModel = new AppFillModel($this->db);
            $thirdPartyGuaranteeModel = new ThirdPartyGuaranteeModel();

            $mastersettingModel = new MastersettingModel($this->db);
            $customerBranchModel = new CustomerBranchModel($this->db);
            $companymasterotherModel = new CompanymasterotherModel($this->db);
            $companymasterotherdetailModel = new CompanymasterotherdetailModel($this->db);
            $companyModel = new CompanyModel($this->db);
            $CompanyApiModel = new CompanyApiModel($this->db);
            $ComCountryListModel = new ComCountryListModel($this->db);

            $directors_info = $directorModel->getAllDirectorsByApplicationIID($application_info->IID);
            $guarantors_info = $guarantorModel->getAllGuarantorByApplicationIID($application_info->IID);

            $res_3rd_party_guarantee = $thirdPartyGuaranteeModel->getAll3rdPartyGuaranteeByApplicationIID($application_info->IID);

            //$references_info = $referenceModel->getAllReferenceByApplicationIID($application_info->IID);
            $references_info = array();
            //$suppliers_info = $supplierModel->getAllSuppliersByApplicationIID($application_info->IID);
            $suppliers_info = array();
            //$fill_info = $appFillModel->getFillInfoByApplicationIID($application_info->IID);

            $res_companyModel = $companyModel->getCompanyByID($application_info->ICOMPANY_ID);

            /*$res_com_api = $CompanyApiModel->getCompanyApiByID($application_info->ICOMPANY_ID);

            $file_write_doc_path = substr($res_com_api->VCONTRACT_PATH, 0, -5);*/

            $where_publish = 1;

            $where_privacy_policy_pdf = 'pdf_privacy_policy';
            $where_terms_condition_pdf = 'pdf_term_and_conditions';
            $where_terms_guarantee_pdf = 'guarantee_policy';
            /* 2019-05-22 */
            /*f($application_info->IMANUAL_APP == 1){
				$where_terms_guarantee_pdf = 'guarantee_policy_manual';
			} else {
				$where_terms_guarantee_pdf = 'guarantee_policy';
			}*/
            /* 2019-05-22 */

            $sql_statement_policy = $this->db->query_select_to_secure('TBL_COMPANY_POLICY', 'IID, VURL, IPUBLISH, VTITLE, VPDF, VEXTERNAL_URL, VDESCRIPTION, ICOMPANY_ID, VPOLICY_CATEGORY', 'IPUBLISH =? AND ICOMPANY_ID=? AND VPOLICY_CATEGORY=?', 'IID ASC', 0, 0);
            $privacy_policy_pdf = $this->db->query_secure($sql_statement_policy, array($where_publish, $application_info->ICOMPANY_ID, $where_privacy_policy_pdf), TRUE, TRUE, "|");

            $sql_statement_terms_con = $this->db->query_select_to_secure('TBL_COMPANY_POLICY', 'IID, VURL, IPUBLISH, VTITLE, VPDF, VEXTERNAL_URL, VDESCRIPTION, ICOMPANY_ID, VPOLICY_CATEGORY', 'IPUBLISH =? AND ICOMPANY_ID=? AND VPOLICY_CATEGORY=?', 'IID ASC', 0, 0);
            $terms_condition_pdf = $this->db->query_secure($sql_statement_terms_con, array($where_publish, $application_info->ICOMPANY_ID, $where_terms_condition_pdf), TRUE, TRUE, "|");

            /* 2019-05-22 */
            $sql_statement_guarantee = $this->db->query_select_to_secure('TBL_COMPANY_POLICY', 'IID, VURL, IPUBLISH, VTITLE, VPDF, VEXTERNAL_URL, VDESCRIPTION, ICOMPANY_ID, VPOLICY_CATEGORY', 'IPUBLISH =? AND ICOMPANY_ID=? AND VPOLICY_CATEGORY=?', 'IID ASC', 0, 0);
            $guarantee_terms_pdf = $this->db->query_secure($sql_statement_guarantee, array($where_publish, $application_info->ICOMPANY_ID, $where_terms_guarantee_pdf), TRUE, TRUE, "|");
            /* 2019-05-22 */

            $output_path = $s3_file_path;
            if (in_array($application_info->ICOMPANY_ID, array(1))) {
                $output_path = '../au/reece';
                $pdf_html_style = 'pdf_html/au_reece/style.css';
                $pdf_html_header = 'pdf_html/au_reece/pdf_html_header';
                if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                    $pdf_html_body = 'pdf_html/au_reece/pdf_html_body_com';
                } else {
                    $pdf_html_body = 'pdf_html/au_reece/pdf_html_body_ind';
                }
            } else if (in_array($application_info->ICOMPANY_ID, array(2))) {
                $output_path = '../au/metalflex';
                $pdf_html_style = 'pdf_html/au_metalflex/style.css';
                $pdf_html_header = 'pdf_html/au_metalflex/pdf_html_header';
                if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                    $pdf_html_body = 'pdf_html/au_metalflex/pdf_html_body_com';
                } else {
                    $pdf_html_body = 'pdf_html/au_metalflex/pdf_html_body_ind';
                }
            } else if (in_array($application_info->ICOMPANY_ID, array(3))) {
                $output_path = '../au/actrol';
                $pdf_html_style = 'pdf_html/au_actrol/style.css';
                $pdf_html_header = 'pdf_html/au_actrol/pdf_html_header';
                if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                    $pdf_html_body = 'pdf_html/au_actrol/pdf_html_body_com';
                } else {
                    $pdf_html_body = 'pdf_html/au_actrol/pdf_html_body_ind';
                }
            } else if (in_array($application_info->ICOMPANY_ID, array(4))) {
                $output_path = '../au/viadux';
                $pdf_html_style = 'pdf_html/au_viadux/style.css';
                $pdf_html_header = 'pdf_html/au_viadux/pdf_html_header';
                if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                    $pdf_html_body = 'pdf_html/au_viadux/pdf_html_body_com';
                } else {
                    $pdf_html_body = 'pdf_html/au_viadux/pdf_html_body_ind';
                }
            } else if (in_array($application_info->ICOMPANY_ID, array(5))) {
                $output_path = '../nz/reece';
                $pdf_html_style = 'pdf_html/nz_reece/style.css';
                $pdf_html_header = 'pdf_html/nz_reece/pdf_html_header';
                if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                    $pdf_html_body = 'pdf_html/nz_reece/pdf_html_body_com';
                } else {
                    $pdf_html_body = 'pdf_html/nz_reece/pdf_html_body_ind';
                }
            }

            $s3ClientService = new S3ClientService(S3_BUCKET);
            $companyLogoResp = $s3ClientService->s3GetObject(S3_BUCKET_PATH . "/pdf_logo/" . $res_companyModel->VPDF_LOGO);
            $companyLogo = "";
            if ($companyLogoResp['code']) {
                $imgData = base64_encode($companyLogoResp['body']);
                $companyLogo = "data:{$companyLogoResp['contentType']};base64, $imgData";
            }

            $companyGroupLogoResp = $s3ClientService->s3GetObject(S3_BUCKET_PATH . "/pdf_logo/reece_group_logo.jpg");
            $companyGroupLogo = "";
            if ($companyGroupLogoResp['code']) {
                $imgData = base64_encode($companyGroupLogoResp['body']);
                $companyGroupLogo = "data:{$companyGroupLogoResp['contentType']};base64, $imgData";
            }

            $data_array = array(
                'application_info' => $application_info,
                'directors_info' => $directors_info,
                'guarantors_info' => $guarantors_info,
                'references_info' => $references_info,
                'res_3rd_party_guarantee' => $res_3rd_party_guarantee,
                'suppliers_info' => $suppliers_info,
                'privacy_policy_pdf' => $privacy_policy_pdf,
                'terms_condition_pdf' => $terms_condition_pdf,
                'guarantee_terms_pdf' => $guarantee_terms_pdf,
                'mastersettingModel' => $mastersettingModel,
                'ComCountryListModel' => $ComCountryListModel,
                'customerBranchModel' => $customerBranchModel,
                'companymasterotherModel' => $companymasterotherModel,
                'companymasterotherdetailModel' => $companymasterotherdetailModel,
                'COMPANY_LOGO' => $companyLogo,
                'res_companyModel' => $res_companyModel,
                'COMPANY_GROUP_LOGO' => $companyGroupLogo
            );

            $this->generatePDF_HTML_DOC($pdf_html_style, $pdf_html_header, $pdf_html_body, $data_array, $caseManagerURL, $output_path);
            return $output_path;
        }
    }

    /**
     *
     * @param type $pdf_html_header
     * @param type $pdf_html_body
     * @param type $data_array
     * @param type $caseManagerURL
     */
    public function generatePDF_HTML_DOC($pdf_html_style, $pdf_html_header, $pdf_html_body, $data_array, $caseManagerURL, $output_path)
    {

        $IDP3_APP_ID = $data_array['application_info']->IDP3_APPLICATION_ID;
        $APP_VURL = $data_array['application_info']->VURL;

        $header = $this->loadHtmlView($pdf_html_header, $data_array);

        $letter = $this->loadHtmlView($pdf_html_body, $data_array);

        include($caseManagerURL . "/classes/mPDF/mpdf.php");

        $stylesheet = file_get_contents($caseManagerURL . '/' . $pdf_html_style);

        $mpdf = new mPDF('c');
        $mpdf->WriteHTML($header);
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($letter, 2);
        $pdf_content = $mpdf->Output('', 'S');

        //upload pdf
        $s3ClientService = new S3ClientService(S3_BUCKET);
        $s3ClientService->s3PutObject($output_path . '/pdf/' . $IDP3_APP_ID . '_' . $APP_VURL . '.pdf', 'data:application/pdf;base64,' . base64_encode($pdf_content), 'application/pdf');

        $generated_html_file = "<style>" . $stylesheet . "</style>" . $letter;

        //upload html
        $s3ClientService->s3PutObject($output_path . '/html/' . $IDP3_APP_ID . '_' . $APP_VURL . '.html', 'data:text/html;base64,' . base64_encode($generated_html_file), 'text/html');

        return true;
    }

    public function viewBureauReportPdfDoc()
    {
        $vurl = check_submit_var($_REQUEST["vurl"], 'V', 0, 0, 1, '');

        $DP3bureaureportModel = new DP3bureaureportModel($this->db);
        $res_dp3 = $DP3bureaureportModel->getDP3bureaureportByVURL($vurl);
        if (!empty($res_dp3)) {
            echo "<style>body{ margin: 0px !important; }</style><object width='100%' height='100%' data= 'data:application/pdf;base64, $res_dp3->VFILE'></object>";
        }
    }

    /**
     *
     * @param type $fullURLfront
     */
    public function updateApplicationDP3Status()
    {
        $fullURLfront = $this->fullURLfront;

        //$com_vurl = check_submit_var($_GET['com_vurl'], 'V', 0, 0, 1, '');

        $c = new App_Sandbox_Cipher(PW_KEY);

        $en_company_id = check_submit_var($_REQUEST["company"], 'V', 0, 0, 1, '');
        $company_id = $c->decrypt($en_company_id);

        $en_app_type = check_submit_var($_REQUEST["app_type"], 'V', 0, 0, 1, '');
        $app_type = $c->decrypt($en_app_type);

        $app_status = $c->encrypt("COMPLETED");

        // if ($company_id != '' && $app_type != '') {
        //     $this->syncApplicationDP3Status($company_id, $app_type);
        // }

        header('Location: ' . $fullURLfront . '/manage-dashboard/dashboard/list.html?company=' . $en_company_id . '&app_type=' . $en_app_type . '&app_status=' . $app_status);
        exit;
    }

    /**
     *
     * @param type $company_id
     * @param type $app_type
     */
    function syncApplicationDP3Status($company_id, $app_type)
    {

        $applicationModel = new ApplicationModel($this->db);

        $application_list = $applicationModel->getApplicationsJoinAppStatus($company_id, $app_type);

        $CompanyModel = new CompanyModel($this->db);
        $CountryModel = new CountryModel($this->db);

        $company_info = $CompanyModel->getCompanyByID($company_id);

        $country_info = array();
        if ($company_info) {
            $country_info = $CountryModel->getCountryByID($company_info->ICOUNTRY_ID);
        }


        if (!empty($application_list)) {

            foreach ($application_list as $application_l) {

                if (!empty($country_info) && $country_info->VCODE == 'AU') {
                    if (strtoupper($app_type) == 'COMPANY') {
                        $this->syncApplicationDP3StatusComAU($company_id, $app_type, $application_l->IDP3_APPLICATION_ID, $application_l->VDP3STATUS, $application_l->VDP3DECISION, $application_l->APP_STATUS_IID);
                    } else if (strtoupper($app_type) == 'INDIVIDUAL') {
                        $this->syncApplicationDP3StatusIndAU($company_id, $app_type, $application_l->IDP3_APPLICATION_ID, $application_l->VDP3STATUS, $application_l->VDP3DECISION, $application_l->APP_STATUS_IID);
                    }
                } else if (!empty($country_info) && $country_info->VCODE == 'NZ') {
                    if (strtoupper($app_type) == 'COMPANY') {
                        $this->syncApplicationDP3StatusComNZ($company_id, $app_type, $application_l->IDP3_APPLICATION_ID, $application_l->VDP3STATUS, $application_l->VDP3DECISION, $application_l->APP_STATUS_IID);
                    } else if (strtoupper($app_type) == 'INDIVIDUAL') {
                        $this->syncApplicationDP3StatusIndNZ($company_id, $app_type, $application_l->IDP3_APPLICATION_ID, $application_l->VDP3STATUS, $application_l->VDP3DECISION, $application_l->APP_STATUS_IID);
                    }
                }
            }
        }
    }

    /**
     *
     * @param type $company_id
     * @param type $app_type
     * @param type $IDP3_APPLICATION_ID
     * @param type $VDP3STATUS
     * @param type $VDP3DECISION
     * @param type $APP_STATUS_IID
     */
    function syncApplicationDP3StatusComAU($company_id, $app_type, $IDP3_APPLICATION_ID, $VDP3STATUS, $VDP3DECISION, $APP_STATUS_IID)
    {

        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($company_id, $app_type);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->Rsp_CompanyBusinessResponse->companyBusinessDecision;
            $dp3_sta = $retrieveResponse->Rsp_CompanyBusinessResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (($VDP3STATUS != $dp3_status || $VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                $update_data = array(
                    array('IPUBLISH', 1),
                    array('VDP3STATUS', $dp3_status),
                    array('VDP3DECISION', $dp3_decision),
                    array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                );

                $AppstatusModel->update($update_data, $APP_STATUS_IID);
            }
        }
    }

    /**
     *
     * @param type $company_id
     * @param type $app_type
     * @param type $IDP3_APPLICATION_ID
     * @param type $VDP3STATUS
     * @param type $VDP3DECISION
     * @param type $APP_STATUS_IID
     */
    function syncApplicationDP3StatusIndAU($company_id, $app_type, $IDP3_APPLICATION_ID, $VDP3STATUS, $VDP3DECISION, $APP_STATUS_IID)
    {

        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($company_id, $app_type);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->Rsp_IndividualCommercialResponse->applicationDecision;
            $dp3_sta = $retrieveResponse->Rsp_IndividualCommercialResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (($VDP3STATUS != $dp3_status || $VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                $update_data = array(
                    array('IPUBLISH', 1),
                    array('VDP3STATUS', $dp3_status),
                    array('VDP3DECISION', $dp3_decision),
                    array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                );

                $AppstatusModel->update($update_data, $APP_STATUS_IID);
            }
        }
    }

    /**
     *
     * @param type $company_id
     * @param type $app_type
     * @param type $IDP3_APPLICATION_ID
     * @param type $VDP3STATUS
     * @param type $VDP3DECISION
     * @param type $APP_STATUS_IID
     */
    function syncApplicationDP3StatusComNZ($company_id, $app_type, $IDP3_APPLICATION_ID, $VDP3STATUS, $VDP3DECISION, $APP_STATUS_IID)
    {

        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($company_id, $app_type);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->OrganisationResponse->finalDecision;
            $dp3_sta = $retrieveResponse->OrganisationResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (($VDP3STATUS != $dp3_status || $VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                $update_data = array(
                    array('IPUBLISH', 1),
                    array('VDP3STATUS', $dp3_status),
                    array('VDP3DECISION', $dp3_decision),
                    array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                );

                $AppstatusModel->update($update_data, $APP_STATUS_IID);
            }
        }
    }

    /**
     *
     * @param type $company_id
     * @param type $app_type
     * @param type $IDP3_APPLICATION_ID
     * @param type $VDP3STATUS
     * @param type $VDP3DECISION
     * @param type $APP_STATUS_IID
     */
    function syncApplicationDP3StatusIndNZ($company_id, $app_type, $IDP3_APPLICATION_ID, $VDP3STATUS, $VDP3DECISION, $APP_STATUS_IID)
    {

        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($company_id, $app_type);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->IndividualResponse->finalDecision;
            $dp3_sta = $retrieveResponse->IndividualResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (($VDP3STATUS != $dp3_status || $VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                $update_data = array(
                    array('IPUBLISH', 1),
                    array('VDP3STATUS', $dp3_status),
                    array('VDP3DECISION', $dp3_decision),
                    array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                );

                $AppstatusModel->update($update_data, $APP_STATUS_IID);
            }
        }
    }

    /**
     * Continue PPSR Details To WSDL
     */
    public function ppsrDetailsContinue()
    {

        $application_vurl = check_submit_var($_POST['application_vurl'], 'V', 0, 0, 1, '');
        $goni = check_submit_var($_POST['goni'], 'V', 0, 0, 1, '');

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($application_vurl);

        if (!empty($application_info)) {

            $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

            $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
            $getPause = $wsdlController->getPause($application_info->IDP3_APPLICATION_ID);

            if (!empty($getPause)) {

                $taskID = '';
                $pauseID = $getPause->PauseMetaResponse->id;

                if (!empty($getPause->PauseMetaResponse->userTasks)) {
                    $taskID = $getPause->PauseMetaResponse->userTasks->id;
                }

                if ($taskID != '' && $pauseID != '') {

                    $companyModel = new CompanyModel($this->db);
                    $res_com = $companyModel->getCompanyByID($application_info->ICOMPANY_ID);

                    $categoryCode = '';

                    if (!empty($res_com)) {

                        if ($res_com->VCATEGORY_CODE != '' && $res_com->VCATEGORY_CODE != NULL) {
                            $categoryCode = $res_com->VCATEGORY_CODE;
                        } else {
                            if ($application_info->ICOMPANY_ID == '1') {
                                $categoryCode = 'CC001';
                            } else if ($application_info->ICOMPANY_ID == '2') {
                                $categoryCode = 'CC002';
                            } else if ($application_info->ICOMPANY_ID == '3') {
                                $categoryCode = 'CC003';
                            }
                        }
                    } else {
                        if ($application_info->ICOMPANY_ID == '1') {
                            $categoryCode = 'CC001';
                        } else if ($application_info->ICOMPANY_ID == '2') {
                            $categoryCode = 'CC002';
                        } else if ($application_info->ICOMPANY_ID == '3') {
                            $categoryCode = 'CC003';
                        }
                    }

                    $taskCategoryCodeTask = $wsdlController->taskCategoryCodeTask($application_info->IDP3_APPLICATION_ID, $taskID, $categoryCode);

                    $AppstatusModel = new AppstatusModel($this->db);
                    $status_info = $AppstatusModel->getStatusByAppID($application_info->IID);

                    $ResumeWait = array();
                    if (!empty($status_info) && strtolower($status_info->VDP3STATUS) != 'completed') {
                        $ResumeWait = $wsdlController->ResumeWait($application_info->IDP3_APPLICATION_ID, $pauseID, 'Continue');
                    }

                    $getPause_new = $wsdlController->getPause($application_info->IDP3_APPLICATION_ID);

                    if (!empty($getPause_new)) {

                        $taskID_new = '';
                        $pauseID_new = $getPause_new->PauseMetaResponse->id;

                        if (!empty($getPause_new->PauseMetaResponse->userTasks)) {
                            $taskID_new = $getPause_new->PauseMetaResponse->userTasks->id;
                        }

                        if ($goni == '') {
                            $goni = $application_info->IDP3_APPLICATION_ID;
                        }

                        $taskGoni = $wsdlController->taskGoni($application_info->IDP3_APPLICATION_ID, $taskID_new, $goni);

                        if (empty(json_decode(json_encode($taskGoni), TRUE))) {
                            $ResumeWait_new = array();
                            if (!empty($status_info) && strtolower($status_info->VDP3STATUS) != 'completed') {
                                $ResumeWait_new = $wsdlController->ResumeWait($application_info->IDP3_APPLICATION_ID, $pauseID_new, 'Continue');
                            }
                        }
                    }
                }
            }
        }

        $data = array(
            'getPause' => $getPause,
            'taskCategoryCodeTask' => $taskCategoryCodeTask,
            'ResumeWait' => $ResumeWait,
            'getPause_new' => $getPause_new,
            'taskGoni' => $taskGoni,
            'ResumeWait_new' => $ResumeWait_new
        );

        echo json_encode($data);
    }

    /**
     *
     */
    public function dp3OverrideDecision()
    {

        $data = array();

        $time_zone = check_submit_var($_POST['time_zone'], 'V', 0, 0, 1, '');
        $vurl = check_submit_var($_POST['application_vurl'], 'V', 0, 0, 1, '');
        $current_decision = check_submit_var($_POST['dp3_current_decision'], 'V', 0, 0, 1, '');
        $decision_option = check_submit_var($_POST['dp3_new_decision_option'], 'V', 0, 0, 1, '');
        $reason_code = check_submit_var($_POST['dp3_new_decision_option_reason_code'], 'V', 0, 0, 1, '');
        $note = check_submit_var($_POST['note'], 'V', 0, 0, 1, '');

        if ($note == '') {
            $note = $reason_code;
        }

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($vurl);

        if (!empty($application_info)) {

            $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

            $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
            $overrideDecision = $wsdlController->overrideDecision($application_info->IDP3_APPLICATION_ID, $decision_option, $reason_code, $note);

            if (empty(json_decode(json_encode($overrideDecision), true))) {

                $DP3decisionoverideModel = new DP3decisionoverideModel($this->db);

                $data_dp3 = array(
                    array('VURL', sha1(rand())),
                    array('IAPPLICATION_ID', $application_info->IID),
                    array('VPREVIOUS_DECISION', $current_decision),
                    array('VNEW_DECISION', $decision_option),
                    array('IOVERIDE_BY', $_SESSION['user_id']),
                    array('DOVERIDE_DATE', date('Y-m-d H:i:s')),
                    array('VREASONCODE', $reason_code),
                    array('TNOTES', $note),
                    array('IPUBLISH', 1)
                );

                $DP3decisionoverideModel->insert($data_dp3);

                $NotesModel = new NotesModel($this->db);

                $vurl = sha1(rand());

                $data_arr = array();

                $converted_datetime = get_country_datetime($time_zone, date('Y-m-d H:i:s'), 'Y-m-d h:i:s A');

                $note_text = $current_decision . ' decision overridden to ' . $decision_option . ' by ' . $_SESSION['user_name'] . ' on ' . $converted_datetime;

                array_push($data_arr, array('VURL', $vurl));
                array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
                array_push($data_arr, array('VCATEGORY', 'case_manager'));
                array_push($data_arr, array('TNOTE', $note_text));
                array_push($data_arr, array('VSECTION', 'decision_override'));
                array_push($data_arr, array('IADDED_BY', $_SESSION['user_id']));
                array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
                array_push($data_arr, array('IPUBLISH', 1));

                $NotesModel->insert($data_arr);

                $data = array(
                    'status' => 'success',
                    'resume' => $overrideDecision
                );
            } else {
                $data = array(
                    'status' => 'error',
                    'resume' => $overrideDecision
                );
            }
        } else {
            $data = array(
                'status' => 'error'
            );
        }

        echo json_encode($data);
    }

    /**
     * get DP3 decision override reasons
     */
    public function getDP3DecisionOverideOption()
    {
        $new_decision_option = check_submit_var($_POST['new_decision_option'], 'V', 0, 0, 1, '');
        $reasons = $_SESSION['dp3_decision_option_' . $new_decision_option];

        $html = '<option value="">Select</option>';

        if (!empty($reasons)) {
            foreach ($reasons as $reason) {
                $html .= '<option value="' . $reason->code . '">' . $reason->description . '</option>';
            }
        }
        echo $html;
    }

    //dp3 decisioning tasks
    public function dp3DecisioningTasksResume()
    {

        $data = array();

        $time_zone = check_submit_var($_POST['time_zone'], 'V', 0, 0, 1, '');
        $vurl = check_submit_var($_POST['application_vurl'], 'V', 0, 0, 1, '');
        $dp3_app_id = check_submit_var($_POST['dp3_app_id'], 'V', 0, 0, 1, '');
        $pause_id = check_submit_var($_POST['pause_id'], 'V', 0, 0, 1, '');
        $resume_option = check_submit_var($_POST['resume_option'], 'V', 0, 0, 1, '');
        $reason = check_submit_var($_POST['reason'], 'V', 0, 0, 1, '');
        $note = check_submit_var($_POST['note'], 'V', 0, 0, 1, '');

        if ($note == '') {
            $note = $resume_option;
        }

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($vurl);

        if (!empty($application_info)) {

            $NotesModel = new NotesModel($this->db);

            $vurl = sha1(rand());

            $data_arr = array();

            $converted_datetime = get_country_datetime($time_zone, date('Y-m-d H:i:s'), 'Y-m-d h:i:s A');

            $note_text = 'Resume Option Selected to ' . $reason . ' by ' . $_SESSION['user_name'] . ' on ' . $converted_datetime . '. Note : ' . $note;

            array_push($data_arr, array('VURL', $vurl));
            array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
            array_push($data_arr, array('VCATEGORY', 'case_manager'));
            array_push($data_arr, array('TNOTE', $note_text));
            array_push($data_arr, array('VSECTION', 'decisioning_tasks'));
            array_push($data_arr, array('IADDED_BY', $_SESSION['user_id']));
            array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
            array_push($data_arr, array('IPUBLISH', 1));

            $NotesModel->insert($data_arr);

            $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

            $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');

            $AppstatusModel = new AppstatusModel($this->db);
            $status_info = $AppstatusModel->getStatusByAppID($application_info->IID);

            $resume = array();
            if (!empty($status_info) && strtolower($status_info->VDP3STATUS) != 'completed') {
                $resume = $wsdlController->Resume($dp3_app_id, $pause_id, $resume_option, $reason, $note);
            }

            if (empty(json_decode(json_encode($resume), true))) {

                $this->updateDecisioningTasksStatus($status_info, 'COMPLETED');

                $data = array(
                    'status' => 'success',
                    'resume' => $resume
                );
            } else {
                $data = array(
                    'status' => 'error',
                    'resume' => $resume
                );
            }
        } else {
            $data = array(
                'status' => 'error'
            );
        }

        echo json_encode($data);
    }

    //end of dp3 decisioning tasks
    //only for testing purpose
    public function wsdlFuncCheck()
    {
        //$wsdl = $this->getWSDLCredentials(4, 'individual');
        $wsdl = $this->getWSDLCredentials(1, 'company');
        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');

        $res = $wsdlController->checkSubmitTest();
        echo '<pre>';
        print_r($res);
        echo '</pre>';
        echo '<br/>';

        //print_r($res->PauseMetaResponse->resume);
        //echo '</pre>';
    }

    public function testWSDLFunc()
    {
        $wsdl = $this->getWSDLCredentials(1, 'company');
        //$wsdl = $this->getWSDLCredentials(4, 'individual');
        //$wsdlController = new WSDLController($this->db, 'https://test.decisionpoint3.com.au/poc/StandardTradeCreditCommercial/CompanyBusinessService?wsdl', 'xgen.account', 'password', 'PasswordText');
        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');

        $res = $wsdlController->getDecisionOptionsWSDL();
        echo '<pre>';
        print_r(json_decode(json_encode($res), TRUE));
        echo '</pre>';
        echo '<br/>';

        //print_r($res->PauseMetaResponse->resume);
        //echo '</pre>';
    }

    //end of only for testing purpose

    /*2019-11-28*/
    /**
     * @prasanna
     * check before resubmit
     */
    public function checkBeforeResubmit($app_iid = "", $application_info = array())
    {

        $response = false;

        if ($app_iid != "" && !empty($application_info)) {

            $appstatusModel = new AppstatusModel($this->db);
            $status_info = $appstatusModel->getStatusByAppID($app_iid);

            if (!empty($status_info)) {
                if (strtoupper($status_info->VDP3DECISION) == "REFER" || strtoupper($status_info->VDP3DECISION) == "PRE_REFER") {
                    $res_dp3_resume_options = $this->getDP3DecisioningTasks($application_info);
                    if (!empty($res_dp3_resume_options)) {

                        $pause_id = $res_dp3_resume_options->id;
                        $override_withdraw_option = false;
                        foreach ($res_dp3_resume_options->resume as $res_dp3_resume) {
                            if (strtoupper($res_dp3_resume->name) == "WITHDRAW") {
                                $override_withdraw_option = true;
                            }
                        }

                        if ($override_withdraw_option) {
                            $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);
                            $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
                            $resume = $wsdlController->Resume($application_info->IDP3_APPLICATION_ID, $pause_id, "Withdraw", "", "");
                            if (empty(json_decode(json_encode($resume), true))) {
                                $response = true;
                            } else {
                                $response = false;
                            }
                        }
                    }
                } else {
                    $response = true;
                }
            }
        }

        return $response;
    }

    public function getDP3DecisioningTasks($application_info)
    {
        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);
        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $getPause = $wsdlController->getPause($application_info->IDP3_APPLICATION_ID);
        return $getPause->PauseMetaResponse;
    }
    /*2019-11-28*/

    /**
     * @prasanna
     * resubmit application form to WSDL
     */
    public function resubmitApplicationForm()
    {

        $vurl = check_submit_var($_POST['VURL'], 'V', 0, 0, 1, '');

        $ApplicationController = new ApplicationController($this->db);
        $CompanyModel = new CompanyModel($this->db);
        $CountryModel = new CountryModel($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($vurl);

        if ($application_info) {

            $company_info = $CompanyModel->getCompanyByID($application_info->ICOMPANY_ID);

            if ($company_info) {

                $country_info = $CountryModel->getCountryByID($company_info->ICOUNTRY_ID);

                if ($country_info) {

                    /*add new IDP3_APPLICATION_ID from WSDL*/
                    $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

                    $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
                    /*$IDP3_generatedId = $wsdlController->generateId();*/

                    $res_check_resubmit = $this->checkBeforeResubmit($application_info->IID, $application_info);

                    if ($res_check_resubmit) {

                        $appEmailLogModel = new AppEmailLogModel($this->db);
                        $applicationModel = new ApplicationModel($this->db);
                        $directorModel = new DirectorModel($this->db);
                        $guarantorModel = new GuarantorModel($this->db);
                        $referenceModel = new ReferenceModel($this->db);
                        $supplierModel = new SupplierModel($this->db);
                        $appstatusModel = new AppstatusModel($this->db);

                        $application_data_array = array();
                        foreach ($application_info as $k => $v) {
                            array_push($application_data_array, array($k, $v));
                        }

                        //get directors
                        $directors_info = $directorModel->getAllDirectorsByApplicationIID($application_info->IID);

                        $all_director_data_array = array();

                        foreach ($directors_info as $directors_inf) {

                            $director_data_array = array();

                            foreach ($directors_inf as $k => $v) {
                                array_push($director_data_array, array($k, $v));
                            }

                            array_push($all_director_data_array, $director_data_array);

                            unset($director_data_array);
                        }

                        //get guarantors
                        $guarantors_info = $guarantorModel->getAllGuarantorByApplicationIID($application_info->IID);
                        $all_guarantors_data_array = array();

                        foreach ($guarantors_info as $guarantors_inf) {

                            $guarantors_data_array = array();

                            foreach ($guarantors_inf as $k => $v) {
                                array_push($guarantors_data_array, array($k, $v));
                            }

                            array_push($all_guarantors_data_array, $guarantors_data_array);
                            unset($guarantors_data_array);
                        }

                        //get WSDL credentials
                        $res = array(1);
                        if ($country_info->VCODE == 'AU') {
                            $res = $wsdlController->SubmitAU($application_data_array, $all_director_data_array, $all_guarantors_data_array, array(), array());
                        } else if ($country_info->VCODE == 'NZ') {
                            $res = $wsdlController->SubmitNZnew($application_data_array, $all_director_data_array, $all_guarantors_data_array, array(), array());
                        }

                        /*check if watchlist application*/
                        $this->applyWatchList($application_info, $directors_info);
                        /*check if watchlist application*/

                        //generate PDF
                        $this->generatePDF_HTML($application_info->VURL, $this->caseManagerURL);
                        //generate PDF

                        //check app status
                        $res_apps = $appstatusModel->getStatusByAppID($application_info->VURL);

                        $companyApiModel = new CompanyApiModel();

                        $API_DATA = $companyApiModel->getCompanyApiByID($application_info->ICOMPANY_ID);

                        if (!empty($API_DATA)) {

                            $API_ID = $API_DATA->VAPPID;
                            $API_KEY = $API_DATA->VSECRETKEY;
                            $API_ACCNO = $API_DATA->VACCOUNTNO;
                            $API_PASSWORD = $API_DATA->VPASSWORD;
                            $CONTRACT_PATH = $API_DATA->VCONTRACT_PATH;
                            $api_link = $this->API_URL . "/api";
                            $email_link = $this->API_URL . '/home.html?SIGNURL=';
                            $s3_file_path = $API_DATA->VS3BUCKETPATH;

                            $application_data = array(
                                array(
                                    "APPLICATIONID" => $application_info->IID,
                                    "CONCODE" => $application_info->IDP3_APPLICATION_ID,
                                    "APPLICANTID" => $application_info->VURL,
                                    "APPLICANT_TYPE" => "1",
                                    "FNAME" => $application_info->VPRI_FIRST_NAME,
                                    "LNAME" => $application_info->VPRI_LAST_NAME,
                                    "DOB" => "",
                                    "PASSPORT" => "NONE",
                                    "DRIVINGLICENSE" => "NONE",
                                    "EMAIL" => $application_info->VPRI_EMAIL,
                                    "MOBILE" => $application_info->VPRI_MOBILE
                                )
                            );

                            foreach ($directors_info as $directors) {

                                $DVURL = $directors->VURL;
                                $DVFIRST_NAME = $directors->VFIRST_NAME;
                                $DVLAST_NAME = $directors->VLAST_NAME;
                                $DVEMAIL = $directors->VEMAIL;
                                $DVMOBILE = $directors->VMOBILE;
                                $DDDOB = $directors->DDOB;
                                $DVDRIVER_LICENCE_NO = $directors->VDRIVER_LICENCE_NO;


                                if ($application_info->VAPPLICATION_TYPE == 'company') {
                                    $director_type = 2;
                                } else if ($application_info->VAPPLICATION_TYPE == 'individual') {
                                    $director_type = 5;
                                }

                                $director = array(
                                    "APPLICATIONID" => $application_info->IID,
                                    "CONCODE" => $application_info->IDP3_APPLICATION_ID,
                                    "APPLICANTID" => $DVURL,
                                    "APPLICANT_TYPE" => $director_type,
                                    "FNAME" => $DVFIRST_NAME,
                                    "LNAME" => $DVLAST_NAME,
                                    "DOB" => $DDDOB,
                                    "PASSPORT" => "NONE",
                                    "DRIVINGLICENSE" => $DVDRIVER_LICENCE_NO,
                                    "EMAIL" => $DVEMAIL,
                                    "MOBILE" => $DVMOBILE
                                );
                                array_push($application_data, $director);
                            }

                            foreach ($guarantors_info as $guarantors) {

                                $GVURL = $guarantors->VURL;
                                $GVFIRST_NAME = $guarantors->VFIRST_NAME;
                                $GVLAST_NAME = $guarantors->VLAST_NAME;
                                $GVEMAIL = $guarantors->VEMAIL;
                                $GVMOBILE = $guarantors->VPHONE;

                                $guarantor = array(
                                    "APPLICATIONID" => $application_info->IID,
                                    "CONCODE" => $application_info->IDP3_APPLICATION_ID,
                                    "APPLICANTID" => $GVURL,
                                    "APPLICANT_TYPE" => "3",
                                    "FNAME" => $GVFIRST_NAME,
                                    "LNAME" => $GVLAST_NAME,
                                    "DOB" => "",
                                    "PASSPORT" => "NONE",
                                    "DRIVINGLICENSE" => "NONE",
                                    "EMAIL" => $GVEMAIL,
                                    "MOBILE" => $GVMOBILE
                                );


                                array_push($application_data, $guarantor);
                            }

                            $sig = $this->getAPISignature('init-signatary', "init-signatary", $API_KEY, $API_ACCNO, $API_PASSWORD);
                            $url_intsign = $api_link . '/init-signatary.html';
                            $data_intsign = array("APPID" => $API_ID, "PCODE" => "init-signatary", "SUB_SEC" => "upd", "SIG" => "$sig", "application" => $application_data);
                            $datareturned_1 = $this->passToAPI($url_intsign, $data_intsign);
                            $test = json_decode($datareturned_1);

                            if ($test->error_code == 100) {

                                $contract_data = array(
                                    array(
                                        "CONTRACT_NAME" => $DP3Code,
                                        "NO_SIGN" => sizeof($test->sign_codes),
                                        "CONTRACT_CODE" => $DP3Code,
                                        "CONTRACT_FILE" => $s3_file_path . '/html/' . $application_info->IDP3_APPLICATION_ID . '_' . $application_info->VURL . '.html'
                                    )
                                );

                                $sig = $this->getAPISignature('contract-details', "contract_details", $API_KEY, $API_ACCNO, $API_PASSWORD);
                                $url = $api_link . '/contract-details.html';
                                $data = array("APPID" => $API_ID, "PCODE" => "contract_details", "SUB_SEC" => "ad", "SIG" => "$sig", "contract" => $contract_data);
                                $datareturned = $this->passToAPI($url, $data);
                            }
                        }

                        if (empty(json_decode(json_encode($res), true))) {

                            $response = array(
                                'status' => 'success'
                            );

                            $applicationLogModel = new ApplicationLogModel($this->db);
                            $applicationLogModel->saveApplicationLog($application_info->IID, "");
                        } else {

                            $response = array(
                                'status' => 'error',
                                'error_msg' => $res
                            );

                            $applicationLogModel = new ApplicationLogModel($this->db);
                            $applicationLogModel->saveApplicationLog($application_info->IID, json_encode($res));
                        }

                        /*update application resubmit status*/
                        $applicationModel->updateApplicationForm(array(array('IRESUBMIT_STATUS', 0)), $application_info->IID);
                        /*update application resubmit status*/
                    } else {
                        $response = array(
                            'status' => 'error'
                        );
                    }
                } else {
                    $response = array(
                        'status' => 'error'
                    );
                }
            } else {
                $response = array(
                    'status' => 'error'
                );
            }
        } else {
            $response = array(
                'status' => 'error'
            );
        }

        echo json_encode($response);
    }

    /*only for manuall run*/
    public function manuallyApplyWatchList()
    {

        $applicationModel = new ApplicationModel($this->db);
        $directorModel = new DirectorModel($this->db);

        $limit = 650;
        $start = 1;

        $application_info_all = $applicationModel->getApplicationsLimit($limit, $start);


        echo '<pre>';
        foreach ($application_info_all as $application_info) {
            $directors_info = $directorModel->getAllDirectorsByApplicationIID($application_info->IID);
            $this->applyWatchList($application_info, $directors_info);
            print_r($application_info);
        }
        echo 'start : ' . $start . '<br>' . 'limit : ' . $limit;
        echo '</pre>';
    }

    public function applyWatchList($application_info, $directors_info)
    {

        $watchlistModel = new WatchlistModel();
        $applicationWatchlistModel = new ApplicationWatchlistModel();

        $res_previous_records = $applicationWatchlistModel->getRecordByAppID($application_info->IID, '', '');
        if (empty($res_previous_records)) {

            $IAPP_WL_ID = 0;

            /*for ABN or ACN*/
            $res_match_records = $watchlistModel->getAllRecordsForApply(1, $application_info->IABN, $application_info->IACN, $application_info->VNZBN, "", "", "", "", "", "", "", "");

            if (!empty($res_match_records)) {
                if ($IAPP_WL_ID == 0) {

                    /*delete before insert*/
                    $applicationWatchlistModel->update(
                        array(
                            array('IPUBLISH', 0),
                            array('IDELETED', 1)
                        ),
                        array(
                            array('IAPPLICATION_ID', $application_info->IID)
                        )
                    );

                    $app_wl_arr = array();

                    array_push($app_wl_arr, array('IPUBLISH', 1));
                    array_push($app_wl_arr, array('IAPPLICATION_ID', $application_info->IID));
                    array_push($app_wl_arr, array('VURL', getToken()));
                    array_push($app_wl_arr, array('IADDED_BY', 0));
                    array_push($app_wl_arr, array('DADDED_AT', date('Y-m-d H:i:s')));
                    array_push($app_wl_arr, array('IDELETED', 0));

                    $IAPP_WL_ID = $applicationWatchlistModel->insert($app_wl_arr);
                    unset($app_wl_arr);
                }
                if ($IAPP_WL_ID > 0) {
                    $this->insertWatchlistInfo($IAPP_WL_ID, 0, $res_match_records, $application_info);
                }
            }

            /*for primary contact*/
            $PRI_FULL_NAME = $application_info->VPRI_FIRST_NAME;
            if (!empty($application_info->VPRI_MIDDLE_NAME)) {
                $PRI_FULL_NAME .= ' ' . $application_info->VPRI_MIDDLE_NAME;
            }
            if (!empty($application_info->VPRI_LAST_NAME)) {
                $PRI_FULL_NAME .= ' ' . $application_info->VPRI_LAST_NAME;
            }
            $res_match_records = $watchlistModel->getAllRecordsForApply(1, "", "", "", "", "", "", "", $PRI_FULL_NAME, $application_info->VPRI_PHONE, $application_info->VPRI_MOBILE, $application_info->VPRI_EMAIL);

            if (!empty($res_match_records)) {
                if ($IAPP_WL_ID == 0) {

                    /*delete before insert*/
                    $applicationWatchlistModel->update(
                        array(
                            array('IPUBLISH', 0),
                            array('IDELETED', 1)
                        ),
                        array(
                            array('IAPPLICATION_ID', $application_info->IID)
                        )
                    );

                    $app_wl_arr = array();

                    array_push($app_wl_arr, array('IPUBLISH', 1));
                    array_push($app_wl_arr, array('IAPPLICATION_ID', $application_info->IID));
                    array_push($app_wl_arr, array('VURL', getToken()));
                    array_push($app_wl_arr, array('IADDED_BY', 0));
                    array_push($app_wl_arr, array('DADDED_AT', date('Y-m-d H:i:s')));
                    array_push($app_wl_arr, array('IDELETED', 0));

                    $IAPP_WL_ID = $applicationWatchlistModel->insert($app_wl_arr);
                    unset($app_wl_arr);
                }
                if ($IAPP_WL_ID > 0) {
                    $this->insertWatchlistInfo($IAPP_WL_ID, 0, $res_match_records, $application_info);
                }
            }

            /*for director and individual*/
            foreach ($directors_info as $directors_inf) {

                $VFULL_NAME = $directors_inf->VFIRST_NAME;
                if (!empty($directors_inf->VMIDDLE_NAME)) {
                    $VFULL_NAME .= ' ' . $directors_inf->VMIDDLE_NAME;
                }
                if (!empty($directors_inf->VLAST_NAME)) {
                    $VFULL_NAME .= ' ' . $directors_inf->VLAST_NAME;
                }

                $VPHONE = "";
                $VMOBILE = "";
                $VEMAIL = "";

                if (!empty($directors_inf->VPHONE)) {
                    $VPHONE = $directors_inf->VPHONE;
                }
                if (!empty($directors_inf->VMOBILE)) {
                    $VMOBILE = $directors_inf->VMOBILE;
                }
                if (!empty($directors_inf->VEMAIL)) {
                    $VEMAIL = $directors_inf->VEMAIL;
                }

                $res_match_records = $watchlistModel->getAllRecordsForApply(1, "", "", "", $VFULL_NAME, $VPHONE, $VMOBILE, $VEMAIL, "", "", "", "");

                if (!empty($res_match_records)) {
                    if ($IAPP_WL_ID == 0) {

                        /*delete before insert*/
                        $applicationWatchlistModel->update(
                            array(
                                array('IPUBLISH', 0),
                                array('IDELETED', 1)
                            ),
                            array(
                                array('IAPPLICATION_ID', $application_info->IID)
                            )
                        );

                        $app_wl_arr = array();

                        array_push($app_wl_arr, array('IPUBLISH', 1));
                        array_push($app_wl_arr, array('IAPPLICATION_ID', $application_info->IID));
                        array_push($app_wl_arr, array('VURL', getToken()));
                        array_push($app_wl_arr, array('IADDED_BY', 0));
                        array_push($app_wl_arr, array('DADDED_AT', date('Y-m-d H:i:s')));
                        array_push($app_wl_arr, array('IDELETED', 0));

                        $IAPP_WL_ID = $applicationWatchlistModel->insert($app_wl_arr);
                        unset($app_wl_arr);
                    }
                    if ($IAPP_WL_ID > 0) {
                        $this->insertWatchlistInfo($IAPP_WL_ID, 1, $res_match_records, $directors_inf);
                    }
                }
            }
        }
    }

    public function insertWatchlistInfo($IAPP_WL_ID, $IDIR, $res_match_records, $app_match_info)
    {

        $applicationWatchlistInfoModel = new ApplicationWatchlistInfoModel();

        $IDIR_IND_ID = 0;
        $IABN = 0;
        $IACN = 0;
        $INZBN = 0;
        $IPRI_FULL_NAME = 0;
        $IPRI_EMAIL = 0;
        $IPRI_MOBILE = 0;
        $IPRI_PHONE = 0;
        $IFULL_NAME = 0;
        $IDOB = 0;
        $IPHONE = 0;
        $IMOBILE = 0;
        $IEMAIL = 0;
        $INICKNAME = 0;
        $ICOMMENTS = 0;

        $data_array = array();

        array_push($data_array, array('IPUBLISH', 1));
        array_push($data_array, array('IAPP_WL_ID', $IAPP_WL_ID));

        foreach ($res_match_records as $res_match_record) {
            if ($IDIR == 0) {
                $IDIR_IND_ID = 0;
                if (!empty($res_match_record->VABN) && $res_match_record->VABN == $app_match_info->IABN) {
                    $IABN = 1;
                }
                if (!empty($res_match_record->VACN) && $res_match_record->VACN == $app_match_info->IACN) {
                    $IACN = 1;
                }
                if (!empty($res_match_record->VNZBN) && $res_match_record->VNZBN == $app_match_info->INZBN) {
                    $INZBN = 1;
                }
                $f_name = $app_match_info->VPRI_FIRST_NAME;
                if (!empty($app_match_info->VPRI_MIDDLE_NAME)) {
                    $f_name .= ' ' . $app_match_info->VPRI_MIDDLE_NAME;
                }
                if (!empty($app_match_info->VPRI_LAST_NAME)) {
                    $f_name .= ' ' . $app_match_info->VPRI_LAST_NAME;
                }
                if (!empty($res_match_record->PRI_FULL_NAME) && strtoupper($res_match_record->PRI_FULL_NAME) == strtoupper($f_name)) {
                    $IPRI_FULL_NAME = 1;
                }
                if (!empty($res_match_record->PRI_EMAIL) && $res_match_record->PRI_EMAIL == $app_match_info->VPRI_EMAIL) {
                    $IPRI_EMAIL = 1;
                }
                if (!empty($res_match_record->PRI_MOBILE) && $res_match_record->PRI_MOBILE == $app_match_info->VPRI_MOBILE) {
                    $IPRI_MOBILE = 1;
                }
                if (!empty($res_match_record->PRI_PHONE) && $res_match_record->PRI_PHONE == $app_match_info->VPRI_PHONE) {
                    $IPRI_PHONE = 1;
                }
            }
            if ($IDIR == 1) {

                $IDIR_IND_ID = $app_match_info->IID;

                $f_name = $app_match_info->VFIRST_NAME;
                if (!empty($app_match_info->VMIDDLE_NAME)) {
                    $f_name .= ' ' . $app_match_info->VMIDDLE_NAME;
                }
                if (!empty($app_match_info->VLAST_NAME)) {
                    $f_name .= ' ' . $app_match_info->VLAST_NAME;
                }
                if (!empty($res_match_record->VFULL_NAME) && strtoupper($res_match_record->VFULL_NAME) == strtoupper($f_name)) {
                    $IFULL_NAME = 1;
                }
                if (!empty($res_match_record->VDOB) && !empty($app_match_info->DDOB) && strtotime($res_match_record->VDOB) == strtotime($app_match_info->DDOB)) {
                    $IDOB = 1;
                }
                if (!empty($res_match_record->VPHONE) && !empty($app_match_info->VPHONE) && $res_match_record->VPHONE == $app_match_info->VPHONE) {
                    $IPHONE = 1;
                }
                if (!empty($res_match_record->VMOBILE) && !empty($app_match_info->VMOBILE) && $res_match_record->VMOBILE == $app_match_info->VMOBILE) {
                    $IMOBILE = 1;
                }
                if (!empty($res_match_record->VEMAIL) && !empty($app_match_info->VEMAIL) && strtoupper($res_match_record->VEMAIL) == strtoupper($app_match_info->VEMAIL)) {
                    $IEMAIL = 1;
                }
            }
        }

        array_push($data_array, array('IDIR_IND_ID', $IDIR_IND_ID));
        array_push($data_array, array('IABN', $IABN));
        array_push($data_array, array('IACN', $IACN));
        array_push($data_array, array('INZBN', $INZBN));
        array_push($data_array, array('IPRI_FULL_NAME', $IPRI_FULL_NAME));
        array_push($data_array, array('IPRI_EMAIL', $IPRI_EMAIL));
        array_push($data_array, array('IPRI_MOBILE', $IPRI_MOBILE));
        array_push($data_array, array('IPRI_PHONE', $IPRI_PHONE));
        array_push($data_array, array('IFULL_NAME', $IFULL_NAME));
        array_push($data_array, array('IDOB', $IDOB));
        array_push($data_array, array('IPHONE', $IPHONE));
        array_push($data_array, array('IMOBILE', $IMOBILE));
        array_push($data_array, array('IEMAIL', $IEMAIL));
        array_push($data_array, array('INICKNAME', $INICKNAME));
        array_push($data_array, array('ICOMMENTS', $ICOMMENTS));

        array_push($data_array, array('VURL', getToken()));
        array_push($data_array, array('IADDED_BY', 0));
        array_push($data_array, array('DADDED_AT', date('Y-m-d H:i:s')));
        array_push($data_array, array('IDELETED', 0));

        $applicationWatchlistInfoModel->insert($data_array);
        unset($data_array);
    }


    /**
     * @prasanna
     * @param type $fullURLfront
     * @param type $vurl
     * @param type $TERM_OF_LOAN
     * @param type $TERM_OF_LOAN_ALUE
     * @param type $mid
     * @return boolean
     */
    public function manageApplicationView()
    {

        $crypt = new App_Sandbox_Cipher(PW_KEY);

        $fullURLfront = $this->fullURLfront;
        $caseManagerURL = $this->caseManagerURL;
        $TERM_OF_LOAN = $this->TERM_OF_LOAN;
        $TERM_OF_LOAN_ALUE = $this->TERM_OF_LOAN_ALUE;
        $mid = $this->mid;

        $vurl = check_submit_var($_REQUEST['vurl'], 'V', 0, 0, 1, '');

        $ApplicationController = new ApplicationController($this->db);

        $application_info = $ApplicationController->getApplicationByVURL($vurl);

        $CompanyModel = new CompanyModel($this->db);

        if ($application_info) {

            $company_info = $CompanyModel->getCompanyByID($application_info->ICOMPANY_ID);

            if ($company_info) {

                $ApplicationModel = new ApplicationModel($this->db);
                $CountryModel = new CountryModel($this->db);
                $AppstatusModel = new AppstatusModel($this->db);
                $Dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);
                $DP3bureaureportModel = new DP3bureaureportModel($this->db);
                $OwnertransferModel = new OwnertransferModel($this->db);
                $DP3decisionoverideModel = new DP3decisionoverideModel($this->db);
                $CompanyuserModel = new CompanyuserModel($this->db);
                $MastersettingModel = new MastersettingModel($this->db);
                $DirectorModel = new DirectorModel($this->db);
                $notesModel = new NotesModel($this->db);
                $processTaskModel = new ProcessTaskModel($this->db);
                $adminTaskModel = new AdminTaskModel($this->db);
                $companymasterotherModel = new CompanymasterotherModel($this->db);
                $companymasterotherdetailModel = new CompanymasterotherdetailModel($this->db);
                $NATcheckModel = new NATcheckModel($this->db);
                $customerBranchModel = new CustomerBranchModel($this->db);
                $attachmentModel = new AttachmentModel();
                $qualityChecksModel = new qualityChecksModel();
                $applicationWatchlistModel = new ApplicationWatchlistModel();
                $companyApiModel = new CompanyApiModel($this->db);

                $company_api = $companyApiModel->getCompanyApiByID($application_info->ICOMPANY_ID);

                /*check if watchlist application*/
                $directors_info_wl = $DirectorModel->getAllDirectorsByApplicationIID($application_info->IID);
                $this->applyWatchList($application_info, $directors_info_wl);
                $application_info = $ApplicationController->getApplicationByVURL($vurl);
                /*check if watchlist application*/

                $country_info = $CountryModel->getCountryByID($company_info->ICOUNTRY_ID);
                $status_info = $AppstatusModel->getStatusByAppID($application_info->IID);
                $dp3reason_code = $Dp3reasoncodeModel->getDP3reasonByAppID($application_info->IID);
                $dp3bureaureport_info = $DP3bureaureportModel->getDP3bureaureportByAppID($application_info->IID);
                $ownertransfer_info = $OwnertransferModel->getOwnerTransferHistory($application_info->IID);
                $dp3decisionoveride_info = $DP3decisionoverideModel->getDP3decisionoverideHistory($application_info->IID);
                $company_users = $CompanyuserModel->getUsersByComID("$application_info->ICOMPANY_ID,0", "", "25");
                $street_type_list = $MastersettingModel->getMasterSettingByCompanyIdByType($application_info->ICOMPANY_ID, 'street_type');
                $state_list = $MastersettingModel->getMasterSettingByCompanyIdByType($application_info->ICOMPANY_ID, 'state');
                $nature_of_business = $MastersettingModel->getMasterSettingByCompanyIdByType($application_info->ICOMPANY_ID, 'nature_of_business');
                $title_list = $MastersettingModel->getMasterSettingByCompanyIdByType($application_info->ICOMPANY_ID, 'title');
                $gender_list = $MastersettingModel->getMasterSettingByCompanyIdByType($application_info->ICOMPANY_ID, 'gender');
                $term_days_list = $MastersettingModel->getMasterSettingByCompanyIdByType($application_info->ICOMPANY_ID, 'term');
                $directors_info = $DirectorModel->getAllDirectorsByApplicationIID($application_info->IID);
                /*$dp3_notes = $notesModel->getAllNotesByAppIdVcategory($application_info->IID, 'DP3', 'ASC');*/
                $casemanager_notes = $notesModel->getAllNotesByAppIdVcategory($application_info->IID, 'case_manager', 1);
                $process_task_info = $processTaskModel->getProcessTaskByAppID($application_info->IID);
                $admin_task_info = $adminTaskModel->getAdminTasksForApplication($application_info->IID);
                $nat_check_list = $NATcheckModel->getNATresByAppId($application_info->IID);
                $credit_amt_multiplier = $companymasterotherModel->getComMasterOtherByComIdByVtype($application_info->ICOMPANY_ID, 'credit_amt_multiplier');
                $r_quality_checks_list = $qualityChecksModel->getQualityChecksByAppId($application_info->IID);
                $watchlist_hit_app = $applicationWatchlistModel->getRecordByAppID($application_info->IID, 1, 0);

                //update Smart Signature status
                $smartSignatureModel = new SmartSignatureModel($this->db);

                $all_res_count = $smartSignatureModel->getApplicantSignatureCount($application_info->IID, '', '');
                $all_received_count = $smartSignatureModel->getApplicantSignatureCount($application_info->IID, 1, 1);

                $pending_sign_apps_count = $smartSignatureModel->getPendingApplicantSignatureCount($application_info->IID, "");
                $pending_verify_apps_count = $smartSignatureModel->getPendingApplicantSignatureCount($application_info->IID, 1);

                $not_require_apps_count = $smartSignatureModel->getNotRQMSapplicantSignatureCount($application_info->IID, 4);
                $manually_sign_apps_count = $smartSignatureModel->getNotRQMSapplicantSignatureCount($application_info->IID, 5);

                if ($pending_verify_apps_count->rec_count > 0) {
                    if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'PENDING VERIFICATION') {
                        $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'PENDING VERIFICATION')), $status_info->IID);
                    }
                } else if (($all_received_count->rec_count + $manually_sign_apps_count->rec_count) > 0) {
                    if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'COMPLETED') {
                        $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'COMPLETED')), $status_info->IID);
                    }
                } else if ($not_require_apps_count->rec_count > 0 && $not_require_apps_count->rec_count == $all_res_count->rec_count) {
                    if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'NOT REQUIRED') {
                        $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'NOT REQUIRED')), $status_info->IID);
                    }
                } else if ($pending_verify_apps_count->rec_count == 0 && ($all_received_count->rec_count + $manually_sign_apps_count->rec_count) == 0 && $not_require_apps_count->rec_count < $all_res_count->rec_count && $pending_sign_apps_count->rec_count > 0) {
                    if (strtoupper($status_info->VSMART_SIGNATURE_STATUS) != 'SIGNATURE PENDING') {
                        $AppstatusModel->update(array(array('VSMART_SIGNATURE_STATUS', 'SIGNATURE PENDING')), $status_info->IID);
                    }
                }
                //end of update Smart Signature status

                $dp3_decisioning_tasks = '';
                $dp3_decision_option = '';
                $esisResponse = array();
                $errorDetails = array();
                $alertDetails = array();

                $bn = "";
                $acn_no = "";
                $previous_applications = array();

                if ($country_info->VCODE == 'AU') {
                    if (!empty($application_info->IABN)) {
                        $bn = $application_info->IABN;
                    }
                    if (!empty($application_info->IACN)) {
                        $acn_no = $application_info->IACN;
                    }
                    if ($bn != "" || $acn_no != "") {
                        $previous_applications = $ApplicationModel->getApplicationsByABN_ACN($application_info->DSUBMITTED_DATE, $country_info->VCODE, $bn, $acn_no, "");
                    }
                } else if ($country_info->VCODE == 'NZ') {
                    if (!empty($application_info->VCOMPANY_REG_NO)) {
                        $bn = $application_info->VCOMPANY_REG_NO;
                        $previous_applications = $ApplicationModel->getApplicationsByABN_ACN($application_info->DSUBMITTED_DATE, $country_info->VCODE, $bn, "", "");
                    }
                }

                //reload status
                $status_info_new = $AppstatusModel->getStatusByAppID($application_info->IID);
                $dp3reason_code_new = $Dp3reasoncodeModel->getDP3reasonByAppID($application_info->IID);
                $dp3bureaureport_info_new = $DP3bureaureportModel->getDP3bureaureportByAppID($application_info->IID);
                /*$dp3_notes_new = $notesModel->getAllNotesByAppIdVcategory($application_info->IID, 'DP3', 'ASC');*/

                if ($country_info) {

                    if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                        //display_element_array
                        $display_element_array = array();
                        $cmSetupScreenModel = new CmSetupScreenModel();
                        $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();

                        $res_cm_setup = $cmSetupScreenModel->getScreenByVALIASComID("COMMERCIAL_APPLICATION_VIEW", $application_info->ICOMPANY_ID);
                        if ($res_cm_setup) {
                            $res_cm_setup_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup->IID);
                            if ($res_cm_setup_info) {
                                foreach ($res_cm_setup_info as $res_cm_setup_inf) {
                                    array_push($display_element_array, $res_cm_setup_inf->VALIAS);
                                }
                            }
                        }
                        //end of display_element_array
                    } else if (strtoupper($application_info->VAPPLICATION_TYPE) == 'INDIVIDUAL') {
                        //display_element_array
                        $display_element_array = array();
                        $cmSetupScreenModel = new CmSetupScreenModel();
                        $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();

                        $res_cm_setup = $cmSetupScreenModel->getScreenByVALIASComID("INDIVIDUAL_APPLICATION_VIEW", $application_info->ICOMPANY_ID);
                        if ($res_cm_setup) {
                            $res_cm_setup_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup->IID);
                            if ($res_cm_setup_info) {
                                foreach ($res_cm_setup_info as $res_cm_setup_inf) {
                                    array_push($display_element_array, $res_cm_setup_inf->VALIAS);
                                }
                            }
                        }
                        //end of display_element_array
                    }

                    /*manual app pdf*/
                    $attch_manual_app_pdf = array();
                    $res_attch_manual_app_pdf = $attachmentModel->getAttachmentByAppiidDocType("manual_application", $application_info->IID);
                    if (!empty($res_attch_manual_app_pdf)) {
                        $attch_manual_app_pdf = $res_attch_manual_app_pdf;
                    }
                    /*manual app pdf*/

                    $data = array(
                        'display_element_array' => $display_element_array,
                        'fullURLfront' => $fullURLfront,
                        'casemanager_url' => $caseManagerURL,
                        'application_info' => $application_info,
                        'company_info' => $company_info,
                        'company_api' => $company_api,
                        'country_info' => $country_info,
                        'status_info' => $status_info_new,
                        'dp3reason_code' => $dp3reason_code_new,
                        'dp3bureaureport_info' => $dp3bureaureport_info_new,
                        'ownertransfer_info' => $ownertransfer_info,
                        'dp3decisionoveride_info' => $dp3decisionoveride_info,
                        'previous_applications' => $previous_applications,
                        'company_users' => $company_users,
                        'street_type_list' => $street_type_list,
                        'state_list' => $state_list,
                        'nature_of_business' => $nature_of_business,
                        'TERM_OF_LOAN' => $TERM_OF_LOAN,
                        'TERM_OF_LOAN_ALUE' => $TERM_OF_LOAN_ALUE,
                        'directors_info' => $directors_info,
                        'title_list' => $title_list,
                        'gender_list' => $gender_list,
                        'db' => $this->db,
                        'mid' => $mid,
                        'casemanager_notes' => $casemanager_notes,
                        'process_task_info' => $process_task_info,
                        'admin_task_info' => $admin_task_info,
                        'term_days_list' => $term_days_list,
                        'dp3_decisioning_tasks' => $dp3_decisioning_tasks,
                        'dp3_decision_option' => $dp3_decision_option,
                        'esisResponse' => $esisResponse,
                        'errorDetails' => $errorDetails,
                        'alertDetails' => $alertDetails,
                        'credit_amt_multiplier' => $credit_amt_multiplier,
                        'nat_check_list' => $nat_check_list,
                        'customerBranchModel' => $customerBranchModel,
                        'smartSignatureModel' => $smartSignatureModel,
                        'attch_manual_app_pdf' => $attch_manual_app_pdf,
                        'r_quality_checks_list' => $r_quality_checks_list,
                        'watchlist_hit_app' => $watchlist_hit_app,
                        'crypt' => $crypt
                    );

                    $view = 'views/manage_application/manage_application';

                    $this->loadView($view, $data);
                } else {
                    return TRUE;
                }
            } else {
                return TRUE;
            }
        } else {
            return TRUE;
        }
    }

    /**
     *
     * @param type $application_info
     */

    public function getDP3Notes($application_info, $dp3_notes)
    {
        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $companyModel = new CompanyModel($this->db);
        $company_dtls = $companyModel->getAllCompanies($application_info->ICOMPANY_ID);

        $country_code = $company_dtls->COUNTRY_CODE;
        $time_zone = 'Australia/Melbourne';
        if ($country_code == "NZ") {
            $time_zone = 'Pacific/Auckland';
        }

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $getDP3Notes = $wsdlController->getNotes($application_info->IDP3_APPLICATION_ID);

        $DP3Notes = $getDP3Notes->GetNotesResponse;

        if (!empty($DP3Notes)) {

            $NotesModel = new NotesModel($this->db);

            //delete dp3 notes
            /*if (!empty($dp3_notes)) {
                $NotesModel->deleteByAppIIDCat($application_info->IID, "dp3");
            }*/
            //end of delete dp3 notes

            /*check if existing note*/
            $dp3_notes_array = array();
            if (!empty($dp3_notes)) {
                foreach ($dp3_notes as $dp3_note_r) {
                    array_push($dp3_notes_array, $dp3_note_r->TNOTE);
                }
            }

            if (!empty($dp3_notes_array)) {

                if (count($DP3Notes->notes) > 1) {

                    foreach ($DP3Notes->notes as $pd3_note) {

                        if (!in_array($pd3_note->note, $dp3_notes_array)) {

                            $vurl = sha1(rand());

                            $data_arr = array();

                            $converted_time = get_country_datetime($time_zone, $pd3_note->createdOn, 'Y-m-d h:i:s A');
                            $note_text = $pd3_note->createdBy . ' on ' . $converted_time; // date('d/m/Y H:i:s A', strtotime($pd3_note->createdOn));

                            array_push($data_arr, array('VURL', $vurl));
                            array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
                            array_push($data_arr, array('VCATEGORY', 'dp3'));
                            array_push($data_arr, array('TNOTE', $pd3_note->note));
                            array_push($data_arr, array('VSECTION', $note_text));
                            array_push($data_arr, array('IADDED_BY', 0));
                            array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
                            array_push($data_arr, array('IPUBLISH', 1));

                            if ($pd3_note->note) {
                                $NotesModel->insert($data_arr);
                            }
                        }
                    }
                } else {

                    if (!in_array($DP3Notes->notes->note, $dp3_notes_array)) {

                        $vurl = sha1(rand());

                        $data_arr = array();

                        $converted_time = get_country_datetime($time_zone, $DP3Notes->notes->createdOn, 'Y-m-d h:i:s A');
                        $note_text = $DP3Notes->notes->createdBy . ' on ' . $converted_time; //date('d/m/Y H:i:s A', strtotime($DP3Notes->notes->createdOn));

                        array_push($data_arr, array('VURL', $vurl));
                        array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
                        array_push($data_arr, array('VCATEGORY', 'dp3'));
                        array_push($data_arr, array('TNOTE', $DP3Notes->notes->note));
                        array_push($data_arr, array('VSECTION', $note_text));
                        array_push($data_arr, array('IADDED_BY', 0));
                        array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
                        array_push($data_arr, array('IPUBLISH', 1));

                        if ($DP3Notes->notes->note) {
                            $NotesModel->insert($data_arr);
                        }
                    }
                }
            } else {
                if (count($DP3Notes->notes) > 1) {

                    foreach ($DP3Notes->notes as $pd3_note) {

                        $vurl = sha1(rand());

                        $data_arr = array();

                        $converted_time = get_country_datetime($time_zone, $pd3_note->createdOn, 'Y-m-d h:i:s A');
                        $note_text = $pd3_note->createdBy . ' on ' . $converted_time; // date('d/m/Y H:i:s A', strtotime($pd3_note->createdOn));

                        array_push($data_arr, array('VURL', $vurl));
                        array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
                        array_push($data_arr, array('VCATEGORY', 'dp3'));
                        array_push($data_arr, array('TNOTE', $pd3_note->note));
                        array_push($data_arr, array('VSECTION', $note_text));
                        array_push($data_arr, array('IADDED_BY', 0));
                        array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
                        array_push($data_arr, array('IPUBLISH', 1));

                        if ($pd3_note->note) {
                            $NotesModel->insert($data_arr);
                        }
                    }
                } else {

                    $vurl = sha1(rand());

                    $data_arr = array();

                    $converted_time = get_country_datetime($time_zone, $DP3Notes->notes->createdOn, 'Y-m-d h:i:s A');
                    $note_text = $DP3Notes->notes->createdBy . ' on ' . $converted_time; //date('d/m/Y H:i:s A', strtotime($DP3Notes->notes->createdOn));

                    array_push($data_arr, array('VURL', $vurl));
                    array_push($data_arr, array('IAPPLICATION_ID', $application_info->IID));
                    array_push($data_arr, array('VCATEGORY', 'dp3'));
                    array_push($data_arr, array('TNOTE', $DP3Notes->notes->note));
                    array_push($data_arr, array('VSECTION', $note_text));
                    array_push($data_arr, array('IADDED_BY', 0));
                    array_push($data_arr, array('DDATETIME', date('Y-m-d H:i:s')));
                    array_push($data_arr, array('IPUBLISH', 1));

                    if ($DP3Notes->notes->note) {
                        $NotesModel->insert($data_arr);
                    }
                }
            }
        }
    }

    /**
     *
     * @param type $application_info
     * @param type $esisResponse
     */
    public function updatePPSRStatusFromWSDL($application_info, $esisResponse, $status_info)
    {

        $ppsrModel = new PpsrModel($this->db);

        $res_ppsr = $ppsrModel->getPPSRForApplication($application_info->IID);

        $status_ppsr = $esisResponse->registrationNumberDetails->registrationNumber->status;

        $status = strtoupper($status_ppsr);

        if ($status == 'PENDING') {
            $status = 'COMPLETED';
        }

        if ($status == '') {
            $AppstatusModel = new AppstatusModel($this->db);

            $update_data = array(
                array('IPUBLISH', 1),
                array('VPPSR_STATUS', 'PENDING'),
                array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
            );

            $AppstatusModel->update($update_data, $status_info->IID);
        } else {
            $AppstatusModel = new AppstatusModel($this->db);

            $update_data = array(
                array('IPUBLISH', 1),
                array('VPPSR_STATUS', strtoupper($status)),
                array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
            );

            $AppstatusModel->update($update_data, $status_info->IID);
        }

        $grantor = $esisResponse->grantorDetails->grantor;

        $data = array(
            array('VURL', sha1(rand())),
            array('IAPPLICATION_ID', $application_info->IID),
            array('VSTATUS', strtoupper($status)),
            array('TDESCRIPTION', $status),
            array('DDATETIME', date('Y-m-d H:i:s')),
            array('IUSERID', $_SESSION['user_id'])
        );

        if (!empty($grantor)) {
            if (count($grantor) > 1) {
                array_push($data, array('VGRANTOR_TYPE', $grantor[0]->type));
                array_push($data, array('VGRANTOR_ORGANISATION_NAME', $grantor[0]->organisationName));
                array_push($data, array('VGRANTOR_ORGANISATION_TYPE', $grantor[1]->organisationType));
                array_push($data, array('VGRANTOR_ORGANISATION_NUMBER', $grantor[1]->organisationNumber));
            } else {
                array_push($data, array('VGRANTOR_TYPE', $grantor->type));
                array_push($data, array('VGRANTOR_ORGANISATION_NAME', $grantor->organisationName));
                array_push($data, array('VGRANTOR_ORGANISATION_TYPE', $grantor->organisationType));
                array_push($data, array('VGRANTOR_ORGANISATION_NUMBER', $grantor->organisationNumber));
            }
        }

        if ($esisResponse->categoryCode) {
            array_push($data, array('VCATEGORY_CODE', $esisResponse->categoryCode));
        }
        if ($esisResponse->goni) {
            array_push($data, array('VGONI_NOTICE', $esisResponse->goni));
        }
        if ($esisResponse->filteredContractTypeCodes) {
            array_push($data, array('VCONTRACT_TYPE_CODE', $esisResponse->filteredContractTypeCodes));
        }

        array_push($data, array('IPUBLISH', 1));

        if (count($data) > 7) {
            if (empty($res_ppsr)) {
                $ppsrModel->savePpsrStatus($data);
            } else {
                $ppsrModel->updatePpsrStatus($data, $res_ppsr->IID);
            }
        }
    }

    /**
     *
     * @param type $application_info
     * @return type
     */
    public function getDecisionOptionsFromDP3($application_info)
    {

        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $getDecisionOptions = $wsdlController->getDecisionOptions($application_info->IDP3_APPLICATION_ID);

        return $getDecisionOptions;
    }

    /**
     * @prasanna
     * @param type $application_info
     * @param type $dp3reason_code
     * @param type $status_info
     * @param type $dp3bureaureport_info
     */
    public function updateDP3DecisionsComNZ($application_info, $dp3reason_code, $status_info)
    {

        $Dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);
        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($application_info->IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->OrganisationResponse->finalDecision;
            $dp3_sta = $retrieveResponse->OrganisationResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (!empty($status_info)) {
                if (($status_info->VDP3STATUS != $dp3_status || $status_info->VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                    $history_data = array();
                    foreach ($status_info as $k => $v) {
                        if (!in_array($k, array('IID'))) {
                            array_push($history_data, array($k, $v));
                        }
                    }

                    $AppstatusModel->insertHistory($history_data);

                    $update_data = array(
                        array('IPUBLISH', 1),
                        array('VDP3STATUS', $dp3_status),
                        array('VDP3DECISION', $dp3_decision),
                        array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                    );

                    $AppstatusModel->update($update_data, $status_info->IID);
                }
            }

            //end of app status
            $dp3_reasons = $retrieveResponse->OrganisationResponse->organisationDecision->decisionResult->reasons;

            if (count($dp3_reasons) > 1) {

                if (!empty($dp3reason_code)) {

                    $dp3reason_code_array = array();

                    foreach ($dp3reason_code as $dp3reason_code_arr) {
                        $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                    }

                    foreach ($dp3_reasons as $dp3_reason) {
                        if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                            $dp3_reason_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('VREASON_CODE', $dp3_reason->reasonCode),
                                array('TDESCRIPTION', $dp3_reason->description),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($dp3_reason->reasonCode != '') {
                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                            }
                        }
                    }

                    unset($dp3reason_code_array);
                } else {

                    foreach ($dp3_reasons as $dp3_reason) {

                        $dp3_reason_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('VREASON_CODE', $dp3_reason->reasonCode),
                            array('TDESCRIPTION', $dp3_reason->description),
                            array('DDATETIME', date('Y-m-d H:i:s'))
                        );

                        if ($dp3_reason->reasonCode != '') {
                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                        }
                    }
                }
            } else {

                if (!empty($dp3reason_code)) {

                    $dp3reason_code_array = array();

                    foreach ($dp3reason_code as $dp3reason_code_arr) {
                        $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                    }

                    if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                        $dp3_reason_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('VREASON_CODE', $dp3_reasons->reasonCode),
                            array('TDESCRIPTION', $dp3_reasons->description),
                            array('DDATETIME', date('Y-m-d H:i:s'))
                        );

                        if ($dp3_reasons->reasonCode != '') {
                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                        }
                    }

                    unset($dp3reason_code_array);
                } else {

                    $dp3_reason_data = array(
                        array('IPUBLISH', 1),
                        array('VURL', sha1(rand())),
                        array('IAPPLICATION_ID', $application_info->IID),
                        array('VREASON_CODE', $dp3_reasons->reasonCode),
                        array('TDESCRIPTION', $dp3_reasons->description),
                        array('DDATETIME', date('Y-m-d H:i:s'))
                    );

                    if ($dp3_reasons->reasonCode != '') {
                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                    }
                }
            }

            /*individual Applicant Decision reasons*/
            $dp3_reasons_ind_applicant = $retrieveResponse->OrganisationResponse->individualApplicantDecision;

            if (!empty($dp3_reasons_ind_applicant)) {
                if (count($dp3_reasons_ind_applicant) > 1) {

                    foreach ($dp3_reasons_ind_applicant as $dp3_reasons_ind_app) {

                        $dp3_reasons_ind = $dp3_reasons_ind_app->decisionResult->reasons;

                        $ind_name = '';
                        if (!empty($dp3_reasons_ind_app->firstName) && !empty($dp3_reasons_ind_app->surname)) {
                            $ind_name = '(' . $dp3_reasons_ind_app->firstName . ' ' . $dp3_reasons_ind_app->surname . ')';
                        }

                        if (count($dp3_reasons_ind) > 1) {

                            if (!empty($dp3reason_code)) {

                                $dp3reason_code_array = array();

                                foreach ($dp3reason_code as $dp3reason_code_arr) {
                                    $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                }

                                foreach ($dp3_reasons_ind as $dp3_reason) {
                                    if (!in_array($dp3_reason->reasonCode . $ind_name, $dp3reason_code_array)) {
                                        $dp3_reason_data = array(
                                            array('IPUBLISH', 1),
                                            array('VURL', sha1(rand())),
                                            array('IAPPLICATION_ID', $application_info->IID),
                                            array('VREASON_CODE', $dp3_reason->reasonCode . $ind_name),
                                            array('TDESCRIPTION', $dp3_reason->description),
                                            array('DDATETIME', date('Y-m-d H:i:s'))
                                        );

                                        if ($dp3_reason->reasonCode != '') {
                                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                                        }
                                    }
                                }

                                unset($dp3reason_code_array);
                            } else {

                                foreach ($dp3_reasons_ind as $dp3_reason) {

                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reason->reasonCode . $ind_name),
                                        array('TDESCRIPTION', $dp3_reason->description),
                                        array('DDATETIME', date('Y-m-d H:i:s'))
                                    );

                                    if ($dp3_reason->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }
                            }
                        } else {

                            if (!empty($dp3reason_code)) {

                                $dp3reason_code_array = array();

                                foreach ($dp3reason_code as $dp3reason_code_arr) {
                                    $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                }

                                if (!in_array($dp3_reasons_ind->reasonCode . $ind_name, $dp3reason_code_array)) {
                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reasons_ind->reasonCode . $ind_name),
                                        array('TDESCRIPTION', $dp3_reasons_ind->description),
                                        array('DDATETIME', date('Y-m-d H:i:s'))
                                    );

                                    if ($dp3_reasons_ind->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }

                                unset($dp3reason_code_array);
                            } else {

                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reasons_ind->reasonCode . $ind_name),
                                    array('TDESCRIPTION', $dp3_reasons_ind->description),
                                    array('DDATETIME', date('Y-m-d H:i:s'))
                                );

                                if ($dp3_reasons_ind->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }
                        }
                    }
                } else {

                    $dp3_reasons_ind = $dp3_reasons_ind_applicant->decisionResult->reasons;

                    $ind_name = '';
                    if (!empty($dp3_reasons_ind_applicant->firstName) && !empty($dp3_reasons_ind_applicant->surname)) {
                        $ind_name = '(' . $dp3_reasons_ind_applicant->firstName . ' ' . $dp3_reasons_ind_applicant->surname . ')';
                    }

                    if (count($dp3_reasons_ind) > 1) {

                        if (!empty($dp3reason_code)) {

                            $dp3reason_code_array = array();

                            foreach ($dp3reason_code as $dp3reason_code_arr) {
                                $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                            }

                            foreach ($dp3_reasons_ind as $dp3_reason) {
                                if (!in_array($dp3_reason->reasonCode . $ind_name, $dp3reason_code_array)) {
                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reason->reasonCode . $ind_name),
                                        array('TDESCRIPTION', $dp3_reason->description),
                                        array('DDATETIME', date('Y-m-d H:i:s'))
                                    );

                                    if ($dp3_reason->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }
                            }

                            unset($dp3reason_code_array);
                        } else {

                            foreach ($dp3_reasons_ind as $dp3_reason) {

                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reason->reasonCode . $ind_name),
                                    array('TDESCRIPTION', $dp3_reason->description),
                                    array('DDATETIME', date('Y-m-d H:i:s'))
                                );

                                if ($dp3_reason->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }
                        }
                    } else {

                        if (!empty($dp3reason_code)) {

                            $dp3reason_code_array = array();

                            foreach ($dp3reason_code as $dp3reason_code_arr) {
                                $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                            }

                            if (!in_array($dp3_reasons_ind->reasonCode . $ind_name, $dp3reason_code_array)) {
                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reasons_ind->reasonCode . $ind_name),
                                    array('TDESCRIPTION', $dp3_reasons_ind->description),
                                    array('DDATETIME', date('Y-m-d H:i:s'))
                                );

                                if ($dp3_reasons_ind->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }

                            unset($dp3reason_code_array);
                        } else {

                            $dp3_reason_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('VREASON_CODE', $dp3_reasons_ind->reasonCode . $ind_name),
                                array('TDESCRIPTION', $dp3_reasons_ind->description),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($dp3_reasons_ind->reasonCode != '') {
                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                            }
                        }
                    }
                }
            }
            /*end of individual Applicant Decision reasons*/

            $res_data = array(
                'errorDetails' => $retrieveResponse->OrganisationResponse->errorDetails,
                'alertDetails' => $retrieveResponse->OrganisationResponse->alertResponses->alertResponse
            );

            return $res_data;
        }
    }

    /**
     *
     * @param type $application_info
     * @return type
     */
    public function getDecisioningTasks($application_info, $status_info)
    {

        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $getPause = $wsdlController->getPause($application_info->IDP3_APPLICATION_ID);

        if (!empty($getPause->PauseMetaResponse->resume)) {
            if (strtoupper($getPause->PauseMetaResponse->resume->name) == 'CONTINUE') {
                $this->updateDecisioningTasksStatus($status_info, 'COMPLETED');
                if (!empty($status_info) && strtolower($status_info->VDP3STATUS) != 'completed') {
                    $wsdlController->Resume($application_info->IDP3_APPLICATION_ID, $getPause->PauseMetaResponse->resume->id, 'Continue', 'Continue', 'Continue');
                }
            } else {
                $this->updateDecisioningTasksStatus($status_info, 'PENDING');
                return $getPause->PauseMetaResponse;
            }
        } else {
            $this->updateDecisioningTasksStatus($status_info, 'COMPLETED');
            if (!empty($status_info) && strtolower($status_info->VDP3STATUS) != 'completed') {
                $wsdlController->Resume($application_info->IDP3_APPLICATION_ID, '', 'Continue', 'Continue', 'Continue');
            }
        }
    }

    /**
     *
     * @param type $status_info
     * @param type $decision_status
     */

    public function updateDecisioningTasksStatus($status_info, $decision_status)
    {
        $AppstatusModel = new AppstatusModel($this->db);

        $update_data = array(
            array('IPUBLISH', 1),
            array('VDECISIONING_TASK_STATUS', $decision_status),
            array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
        );

        $AppstatusModel->update($update_data, $status_info->IID);
    }

    /**
     * @prasanna
     * @param type $application_info
     * @param type $dp3bureaureport_info
     */
    public function dp3BureauReportNZCom($application_info, $dp3bureaureport_info)
    {
        if (!empty($application_info->DSUBMITTED_DATE) && strtotime($application_info->DSUBMITTED_DATE . " +6 months") > strtotime(date("Y-m-d"))) {
            $DP3bureaureportModel = new DP3bureaureportModel($this->db);

            $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

            $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
            $connectorCacheEntryList = $wsdlController->getConnectorCacheEntryList($application_info->IDP3_APPLICATION_ID);

            if (!empty($connectorCacheEntryList)) {

                $cacheEntryList = $connectorCacheEntryList->ConnectorCacheEntryList->CacheEntryList;

                if (count($cacheEntryList) > 1) {

                    if (!empty($dp3bureaureport_info)) {

                        $dp3bureaureport_info_array = array();

                        foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                            $dp3bureaureport_info_array[] = $dp3bureaureport_info_arr->TDESCRIPTION;
                        }

                        foreach ($cacheEntryList as $cacheEntryList_r) {

                            $DP3bureaure_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('IREQUESTED_BY', 1),
                                array('TDESCRIPTION', $cacheEntryList_r->cacheEntryName),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($cacheEntryList_r->cacheEntryName != '') {
                                $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList_r->cacheEntryId);
                                if (!empty($bureau_report_pdf)) {
                                    array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                    array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));

                                    foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                                        if ($cacheEntryList_r->cacheEntryName == $dp3bureaureport_info_arr->TDESCRIPTION && $dp3bureaureport_info_arr->VFILENAME != $bureau_report_pdf['filename']) {
                                            $update_arr = array();
                                            array_push($update_arr, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                            array_push($update_arr, array('VFILENAME', $bureau_report_pdf['filename']));
                                            $DP3bureaureportModel->update($update_arr, $dp3bureaureport_info_arr->IID);
                                            unset($update_arr);
                                        }
                                    }
                                }
                                if (!in_array($cacheEntryList_r->cacheEntryName, $dp3bureaureport_info_array)) {
                                    $DP3bureaureportModel->insert($DP3bureaure_data);
                                }
                            }

                            unset($DP3bureaure_data);
                        }
                    } else {
                        foreach ($cacheEntryList as $cacheEntryList_r) {
                            $DP3bureaure_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('IREQUESTED_BY', 1),
                                array('TDESCRIPTION', $cacheEntryList_r->cacheEntryName),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($cacheEntryList_r->cacheEntryName != '') {
                                $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList_r->cacheEntryId);
                                if (!empty($bureau_report_pdf)) {
                                    array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                    array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));
                                }
                                $DP3bureaureportModel->insert($DP3bureaure_data);
                            }
                            unset($DP3bureaure_data);
                        }
                    }
                } else {

                    if (!empty($dp3bureaureport_info)) {

                        $dp3bureaureport_info_array = array();

                        foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                            $dp3bureaureport_info_array[] = $dp3bureaureport_info_arr->TDESCRIPTION;
                        }

                        $DP3bureaure_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('IREQUESTED_BY', 1),
                            array('TDESCRIPTION', $cacheEntryList->cacheEntryName),
                            array('DDATETIME', date('Y-m-d H:i:s'))
                        );

                        if ($cacheEntryList->cacheEntryName != '') {
                            $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList->cacheEntryId);
                            if (!empty($bureau_report_pdf)) {
                                array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));

                                foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                                    if ($cacheEntryList->cacheEntryName == $dp3bureaureport_info_arr->TDESCRIPTION && $dp3bureaureport_info_arr->VFILENAME != $bureau_report_pdf['filename']) {
                                        $update_arr = array();
                                        array_push($update_arr, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                        array_push($update_arr, array('VFILENAME', $bureau_report_pdf['filename']));
                                        $DP3bureaureportModel->update($update_arr, $dp3bureaureport_info_arr->IID);
                                        unset($update_arr);
                                    }
                                }
                            }
                            if (!in_array($cacheEntryList->cacheEntryName, $dp3bureaureport_info_array)) {
                                $DP3bureaureportModel->insert($DP3bureaure_data);
                            }
                        }
                    } else {

                        $DP3bureaure_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('IREQUESTED_BY', 1),
                            array('TDESCRIPTION', $cacheEntryList->cacheEntryName),
                            array('DDATETIME', date('Y-m-d H:i:s'))
                        );

                        if ($cacheEntryList->cacheEntryName != '') {
                            $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList->cacheEntryId);
                            if (!empty($bureau_report_pdf)) {
                                array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));
                            }
                            $DP3bureaureportModel->insert($DP3bureaure_data);
                        }
                    }
                }
            }
        }
    }

    /**
     * @prasanna
     * @param type $application_info
     * @param type $dp3reason_code
     * @param type $status_info
     * @param type $dp3bureaureport_info
     */
    public function updateDP3DecisionsIndNZ($application_info, $dp3reason_code, $status_info)
    {

        $Dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);
        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($application_info->IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->IndividualResponse->finalDecision;
            $dp3_sta = $retrieveResponse->IndividualResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (!empty($status_info)) {
                if (($status_info->VDP3STATUS != $dp3_status || $status_info->VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                    $history_data = array();
                    foreach ($status_info as $k => $v) {
                        if (!in_array($k, array('IID'))) {
                            array_push($history_data, array($k, $v));
                        }
                    }

                    $AppstatusModel->insertHistory($history_data);

                    $update_data = array(
                        array('IPUBLISH', 1),
                        array('VDP3STATUS', $dp3_status),
                        array('VDP3DECISION', $dp3_decision),
                        array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                    );

                    $AppstatusModel->update($update_data, $status_info->IID);
                }
            }

            //end of app status

            $individualApplicantDecision = $retrieveResponse->IndividualResponse->individualApplicantDecision;

            if (count($individualApplicantDecision) > 1) {

                foreach ($individualApplicantDecision as $individualApplicantDecision_r) {

                    $dp3_reasons = $individualApplicantDecision_r->decisionResult->reasons;

                    if (count($dp3_reasons) > 1) {

                        if (!empty($dp3reason_code)) {

                            $dp3reason_code_array = array();

                            foreach ($dp3reason_code as $dp3reason_code_arr) {
                                $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                            }

                            foreach ($dp3_reasons as $dp3_reason) {
                                if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reason->reasonCode),
                                        array('TDESCRIPTION', $dp3_reason->description),
                                        array('DDATETIME', date('Y-m-d H:i:s'))
                                    );

                                    if ($dp3_reason->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }
                            }

                            unset($dp3reason_code_array);
                        } else {

                            foreach ($dp3_reasons as $dp3_reason) {

                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reason->reasonCode),
                                    array('TDESCRIPTION', $dp3_reason->description),
                                    array('DDATETIME', date('Y-m-d H:i:s'))
                                );

                                if ($dp3_reason->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }
                        }
                    } else {

                        if (!empty($dp3reason_code)) {

                            $dp3reason_code_array = array();

                            foreach ($dp3reason_code as $dp3reason_code_arr) {
                                $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                            }

                            if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reasons->reasonCode),
                                    array('TDESCRIPTION', $dp3_reasons->description),
                                    array('DDATETIME', date('Y-m-d H:i:s'))
                                );

                                if ($dp3_reasons->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }

                            unset($dp3reason_code_array);
                        } else {

                            $dp3_reason_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('VREASON_CODE', $dp3_reasons->reasonCode),
                                array('TDESCRIPTION', $dp3_reasons->description),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($dp3_reasons->reasonCode != '') {
                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                            }
                        }
                    }
                }
            } else {

                $dp3_reasons = $individualApplicantDecision->decisionResult->reasons;

                if (count($dp3_reasons) > 1) {

                    if (!empty($dp3reason_code)) {

                        $dp3reason_code_array = array();

                        foreach ($dp3reason_code as $dp3reason_code_arr) {
                            $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                        }

                        foreach ($dp3_reasons as $dp3_reason) {
                            if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reason->reasonCode),
                                    array('TDESCRIPTION', $dp3_reason->description),
                                    array('DDATETIME', date('Y-m-d H:i:s'))
                                );

                                if ($dp3_reason->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }
                        }

                        unset($dp3reason_code_array);
                    } else {

                        foreach ($dp3_reasons as $dp3_reason) {

                            $dp3_reason_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('VREASON_CODE', $dp3_reason->reasonCode),
                                array('TDESCRIPTION', $dp3_reason->description),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($dp3_reason->reasonCode != '') {
                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                            }
                        }
                    }
                } else {

                    if (!empty($dp3reason_code)) {

                        $dp3reason_code_array = array();

                        foreach ($dp3reason_code as $dp3reason_code_arr) {
                            $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                        }

                        if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                            $dp3_reason_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('VREASON_CODE', $dp3_reasons->reasonCode),
                                array('TDESCRIPTION', $dp3_reasons->description),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($dp3_reasons->reasonCode != '') {
                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                            }
                        }

                        unset($dp3reason_code_array);
                    } else {

                        $dp3_reason_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('VREASON_CODE', $dp3_reasons->reasonCode),
                            array('TDESCRIPTION', $dp3_reasons->description),
                            array('DDATETIME', date('Y-m-d H:i:s'))
                        );

                        if ($dp3_reasons->reasonCode != '') {
                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                        }
                    }
                }
            }

            $res_data = array(
                'errorDetails' => $retrieveResponse->IndividualResponse->errorDetails,
                'alertDetails' => $retrieveResponse->IndividualResponse->alertResponses->alertResponse
            );

            return $res_data;
        }
    }

    /**
     * @prasanna
     * @param type $application_info
     * @param type $dp3bureaureport_info
     */
    public function dp3BureauReportNZInd($application_info, $dp3bureaureport_info)
    {
        if (!empty($application_info->DSUBMITTED_DATE) && strtotime($application_info->DSUBMITTED_DATE . " +6 months") > strtotime(date("Y-m-d"))) {
            $DP3bureaureportModel = new DP3bureaureportModel($this->db);

            $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

            $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
            $connectorCacheEntryList = $wsdlController->getConnectorCacheEntryList($application_info->IDP3_APPLICATION_ID);

            if (!empty($connectorCacheEntryList)) {

                $cacheEntryList = $connectorCacheEntryList->ConnectorCacheEntryList->CacheEntryList;

                if (count($cacheEntryList) > 1) {

                    if (!empty($dp3bureaureport_info)) {

                        $dp3bureaureport_info_array = array();

                        foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                            $dp3bureaureport_info_array[] = $dp3bureaureport_info_arr->TDESCRIPTION;
                        }

                        foreach ($cacheEntryList as $cacheEntryList_r) {

                            $DP3bureaure_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('IREQUESTED_BY', 1),
                                array('TDESCRIPTION', $cacheEntryList_r->cacheEntryName),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($cacheEntryList_r->cacheEntryName != '') {
                                $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList_r->cacheEntryId);
                                if (!empty($bureau_report_pdf)) {
                                    array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                    array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));

                                    foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                                        if ($cacheEntryList_r->cacheEntryName == $dp3bureaureport_info_arr->TDESCRIPTION && $dp3bureaureport_info_arr->VFILENAME != $bureau_report_pdf['filename']) {
                                            $update_arr = array();
                                            array_push($update_arr, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                            array_push($update_arr, array('VFILENAME', $bureau_report_pdf['filename']));
                                            $DP3bureaureportModel->update($update_arr, $dp3bureaureport_info_arr->IID);
                                            unset($update_arr);
                                        }
                                    }
                                }
                                if (!in_array($cacheEntryList_r->cacheEntryName, $dp3bureaureport_info_array)) {
                                    $DP3bureaureportModel->insert($DP3bureaure_data);
                                }
                            }

                            unset($DP3bureaure_data);
                        }
                    } else {
                        foreach ($cacheEntryList as $cacheEntryList_r) {
                            $DP3bureaure_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('IREQUESTED_BY', 1),
                                array('TDESCRIPTION', $cacheEntryList_r->cacheEntryName),
                                array('DDATETIME', date('Y-m-d H:i:s'))
                            );

                            if ($cacheEntryList_r->cacheEntryName != '') {
                                $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList_r->cacheEntryId);
                                if (!empty($bureau_report_pdf)) {
                                    array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                    array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));
                                }
                                $DP3bureaureportModel->insert($DP3bureaure_data);
                            }
                            unset($DP3bureaure_data);
                        }
                    }
                } else {

                    if (!empty($dp3bureaureport_info)) {

                        $dp3bureaureport_info_array = array();

                        foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                            $dp3bureaureport_info_array[] = $dp3bureaureport_info_arr->TDESCRIPTION;
                        }

                        $DP3bureaure_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('IREQUESTED_BY', 1),
                            array('TDESCRIPTION', $cacheEntryList->cacheEntryName),
                            array('DDATETIME', date('Y-m-d H:i:s'))
                        );

                        if ($cacheEntryList->cacheEntryName != '') {
                            $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList->cacheEntryId);
                            if (!empty($bureau_report_pdf)) {
                                array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));

                                foreach ($dp3bureaureport_info as $dp3bureaureport_info_arr) {
                                    if ($cacheEntryList->cacheEntryName == $dp3bureaureport_info_arr->TDESCRIPTION && $dp3bureaureport_info_arr->VFILENAME != $bureau_report_pdf['filename']) {
                                        $update_arr = array();
                                        array_push($update_arr, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                        array_push($update_arr, array('VFILENAME', $bureau_report_pdf['filename']));
                                        $DP3bureaureportModel->update($update_arr, $dp3bureaureport_info_arr->IID);
                                        unset($update_arr);
                                    }
                                }
                            }
                            if (!in_array($cacheEntryList->cacheEntryName, $dp3bureaureport_info_array)) {
                                $DP3bureaureportModel->insert($DP3bureaure_data);
                            }
                        }
                    } else {

                        $DP3bureaure_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('IREQUESTED_BY', 1),
                            array('TDESCRIPTION', $cacheEntryList->cacheEntryName),
                            array('DDATETIME', date('Y-m-d H:i:s'))
                        );

                        if ($cacheEntryList->cacheEntryName != '') {
                            $bureau_report_pdf = $wsdlController->retrieveConnectorReport($application_info->IDP3_APPLICATION_ID, $cacheEntryList->cacheEntryId);
                            if (!empty($bureau_report_pdf)) {
                                array_push($DP3bureaure_data, array('VFILE', $bureau_report_pdf['xml_res_content']));
                                array_push($DP3bureaure_data, array('VFILENAME', $bureau_report_pdf['filename']));
                            }
                            $DP3bureaureportModel->insert($DP3bureaure_data);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @param type $application_info
     * @param type $status_info
     */
    public function updateNewDP3Decisions($application_info, $status_info)
    {
        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $getDecisionOverridesList = $wsdlController->getDecisionOverridesList($application_info->IDP3_APPLICATION_ID);

        if (!empty($getDecisionOverridesList)) {

            $new_dp3_decision = '';
            $override_history_res = $getDecisionOverridesList->DecisionOverrides;

            if (count($override_history_res) > 1) {
                foreach ($override_history_res as $override_history_r) {
                    $new_dp3_decision = $override_history_r->decisionResultNew;
                }
            } else {
                $new_dp3_decision = $override_history_res->decisionResultNew;
            }

            if ($new_dp3_decision != '') {

                $history_data = array();
                foreach ($status_info as $k => $v) {
                    if (!in_array($k, array('IID'))) {
                        array_push($history_data, array($k, $v));
                    }
                }

                $AppstatusModel->insertHistory($history_data);

                $update_data = array(
                    array('IPUBLISH', 1),
                    array('VDP3DECISION', $new_dp3_decision),
                    array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                );

                $AppstatusModel->update($update_data, $status_info->IID);
            }
        }
    }

    /**
     * @prasanna
     * @param type $application_info
     * @param type $dp3reason_code
     * @param type $status_info
     */
    public function updateDP3DecisionsComAU($application_info, $dp3reason_code, $status_info)
    {

        $Dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);
        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($application_info->IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->Rsp_CompanyBusinessResponse->companyBusinessDecision;
            $dp3_sta = $retrieveResponse->Rsp_CompanyBusinessResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (!empty($status_info)) {
                if (($status_info->VDP3STATUS != $dp3_status || $status_info->VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                    $history_data = array();
                    foreach ($status_info as $k => $v) {
                        if (!in_array($k, array('IID'))) {
                            array_push($history_data, array($k, $v));
                        }
                    }

                    $AppstatusModel->insertHistory($history_data);

                    $update_data = array(
                        array('IPUBLISH', 1),
                        array('VDP3STATUS', $dp3_status),
                        array('VDP3DECISION', $dp3_decision),
                        array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                    );

                    $AppstatusModel->update($update_data, $status_info->IID);
                }
            }

            //end of app status
            $dp3_reasons = $retrieveResponse->Rsp_CompanyBusinessResponse->response->organisationResults->organisationDecision->reasons;

            if (count($dp3_reasons) > 1) {

                if (!empty($dp3reason_code)) {

                    $dp3reason_code_array = array();

                    foreach ($dp3reason_code as $dp3reason_code_arr) {
                        $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                    }

                    foreach ($dp3_reasons as $dp3_reason) {
                        if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                            $dp3_reason_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('VREASON_CODE', $dp3_reason->reasonCode),
                                array('TDESCRIPTION', $dp3_reason->description),
                                array('DDATETIME', date('Y-m-d H:i:s')),
                            );

                            if ($dp3_reason->reasonCode != '') {
                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                            }
                        }
                    }

                    unset($dp3reason_code_array);
                } else {

                    foreach ($dp3_reasons as $dp3_reason) {

                        $dp3_reason_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('VREASON_CODE', $dp3_reason->reasonCode),
                            array('TDESCRIPTION', $dp3_reason->description),
                            array('DDATETIME', date('Y-m-d H:i:s')),
                        );

                        if ($dp3_reason->reasonCode != '') {
                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                        }
                    }
                }
            } else {

                if (!empty($dp3reason_code)) {

                    $dp3reason_code_array = array();

                    foreach ($dp3reason_code as $dp3reason_code_arr) {
                        $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                    }

                    if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                        $dp3_reason_data = array(
                            array('IPUBLISH', 1),
                            array('VURL', sha1(rand())),
                            array('IAPPLICATION_ID', $application_info->IID),
                            array('VREASON_CODE', $dp3_reasons->reasonCode),
                            array('TDESCRIPTION', $dp3_reasons->description),
                            array('DDATETIME', date('Y-m-d H:i:s')),
                        );

                        if ($dp3_reasons->reasonCode != '') {
                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                        }
                    }

                    unset($dp3reason_code_array);
                } else {

                    $dp3_reason_data = array(
                        array('IPUBLISH', 1),
                        array('VURL', sha1(rand())),
                        array('IAPPLICATION_ID', $application_info->IID),
                        array('VREASON_CODE', $dp3_reasons->reasonCode),
                        array('TDESCRIPTION', $dp3_reasons->description),
                        array('DDATETIME', date('Y-m-d H:i:s')),
                    );

                    if ($dp3_reasons->reasonCode != '') {
                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                    }
                }
            }

            $res_data = array(
                'esisResponse' => $retrieveResponse->Rsp_CompanyBusinessResponse->response->esisResponse,
                'errorDetails' => $retrieveResponse->Rsp_CompanyBusinessResponse->errorDetails,
                'alertDetails' => $retrieveResponse->Rsp_CompanyBusinessResponse->alertResponses->alertResponse
            );

            return $res_data;
        }
    }

    /**
     * @prasanna
     * @param type $application_info
     * @param type $dp3reason_code
     * @param type $status_info
     */
    public function updateDP3DecisionsIndAU($application_info, $dp3reason_code, $status_info)
    {

        $Dp3reasoncodeModel = new Dp3reasoncodeModel($this->db);
        $AppstatusModel = new AppstatusModel($this->db);

        $wsdl = $this->getWSDLCredentials($application_info->ICOMPANY_ID, $application_info->VAPPLICATION_TYPE);

        $wsdlController = new WSDLController($this->db, $wsdl['WEDA_URL'], $wsdl['WEDA_USERNAME'], $wsdl['WEDA_PASSWORD'], 'PasswordText');
        $retrieveResponse = $wsdlController->retrieveResponse($application_info->IDP3_APPLICATION_ID);

        $dp3_decision = '';
        $dp3_status = 'ONHOLD';

        if (!empty($retrieveResponse)) {

            //update app status
            $dp3_decision = $retrieveResponse->Rsp_IndividualCommercialResponse->applicationDecision;
            $dp3_sta = $retrieveResponse->Rsp_IndividualCommercialResponse->status;

            if ($dp3_sta) {
                $dp3_status = $dp3_sta;
            }

            if (!empty($status_info)) {
                if (($status_info->VDP3STATUS != $dp3_status || $status_info->VDP3DECISION != $dp3_decision) && $dp3_decision != '') {

                    $history_data = array();
                    foreach ($status_info as $k => $v) {
                        if (!in_array($k, array('IID'))) {
                            array_push($history_data, array($k, $v));
                        }
                    }

                    $AppstatusModel->insertHistory($history_data);

                    $update_data = array(
                        array('IPUBLISH', 1),
                        array('VDP3STATUS', $dp3_status),
                        array('VDP3DECISION', $dp3_decision),
                        array('DUPDATED_DATETIME', date('Y-m-d H:i:s'))
                    );

                    $AppstatusModel->update($update_data, $status_info->IID);
                }
            }

            //end of app status

            $additionalApplicantResults_array = $retrieveResponse->Rsp_IndividualCommercialResponse->response->additionalApplicantResults;

            if (count($additionalApplicantResults_array) > 1) {

                foreach ($additionalApplicantResults_array as $additionalApplicantResults_arr) {

                    $additionalApplicantResults = $additionalApplicantResults_arr->additionalApplicantDecision;

                    if (count($additionalApplicantResults) > 1) {

                        foreach ($additionalApplicantResults as $additionalApplicantResults_r) {

                            $dp3_reasons = $additionalApplicantResults_r->reasons;

                            if (count($dp3_reasons) > 1) {

                                if (!empty($dp3reason_code)) {

                                    $dp3reason_code_array = array();

                                    foreach ($dp3reason_code as $dp3reason_code_arr) {
                                        $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                    }

                                    foreach ($dp3_reasons as $dp3_reason) {
                                        if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                                            $dp3_reason_data = array(
                                                array('IPUBLISH', 1),
                                                array('VURL', sha1(rand())),
                                                array('IAPPLICATION_ID', $application_info->IID),
                                                array('VREASON_CODE', $dp3_reason->reasonCode),
                                                array('TDESCRIPTION', $dp3_reason->description),
                                                array('DDATETIME', date('Y-m-d H:i:s')),
                                            );

                                            if ($dp3_reason->reasonCode != '') {
                                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                                            }
                                        }
                                    }

                                    unset($dp3reason_code_array);
                                } else {

                                    foreach ($dp3_reasons as $dp3_reason) {

                                        $dp3_reason_data = array(
                                            array('IPUBLISH', 1),
                                            array('VURL', sha1(rand())),
                                            array('IAPPLICATION_ID', $application_info->IID),
                                            array('VREASON_CODE', $dp3_reason->reasonCode),
                                            array('TDESCRIPTION', $dp3_reason->description),
                                            array('DDATETIME', date('Y-m-d H:i:s')),
                                        );

                                        if ($dp3_reason->reasonCode != '') {
                                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                                        }
                                    }
                                }
                            } else {

                                if (!empty($dp3reason_code)) {

                                    $dp3reason_code_array = array();

                                    foreach ($dp3reason_code as $dp3reason_code_arr) {
                                        $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                    }

                                    if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                                        $dp3_reason_data = array(
                                            array('IPUBLISH', 1),
                                            array('VURL', sha1(rand())),
                                            array('IAPPLICATION_ID', $application_info->IID),
                                            array('VREASON_CODE', $dp3_reasons->reasonCode),
                                            array('TDESCRIPTION', $dp3_reasons->description),
                                            array('DDATETIME', date('Y-m-d H:i:s')),
                                        );

                                        if ($dp3_reasons->reasonCode != '') {
                                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                                        }
                                    }

                                    unset($dp3reason_code_array);
                                } else {

                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reasons->reasonCode),
                                        array('TDESCRIPTION', $dp3_reasons->description),
                                        array('DDATETIME', date('Y-m-d H:i:s')),
                                    );

                                    if ($dp3_reasons->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }
                            }
                        }
                    } else {

                        $dp3_reasons = $additionalApplicantResults->reasons;

                        if (count($dp3_reasons) > 1) {

                            if (!empty($dp3reason_code)) {

                                $dp3reason_code_array = array();

                                foreach ($dp3reason_code as $dp3reason_code_arr) {
                                    $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                }

                                foreach ($dp3_reasons as $dp3_reason) {
                                    if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                                        $dp3_reason_data = array(
                                            array('IPUBLISH', 1),
                                            array('VURL', sha1(rand())),
                                            array('IAPPLICATION_ID', $application_info->IID),
                                            array('VREASON_CODE', $dp3_reason->reasonCode),
                                            array('TDESCRIPTION', $dp3_reason->description),
                                            array('DDATETIME', date('Y-m-d H:i:s')),
                                        );

                                        if ($dp3_reason->reasonCode != '') {
                                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                                        }
                                    }
                                }

                                unset($dp3reason_code_array);
                            } else {

                                foreach ($dp3_reasons as $dp3_reason) {

                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reason->reasonCode),
                                        array('TDESCRIPTION', $dp3_reason->description),
                                        array('DDATETIME', date('Y-m-d H:i:s')),
                                    );

                                    if ($dp3_reason->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }
                            }
                        } else {

                            if (!empty($dp3reason_code)) {

                                $dp3reason_code_array = array();

                                foreach ($dp3reason_code as $dp3reason_code_arr) {
                                    $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                }

                                if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reasons->reasonCode),
                                        array('TDESCRIPTION', $dp3_reasons->description),
                                        array('DDATETIME', date('Y-m-d H:i:s')),
                                    );

                                    if ($dp3_reasons->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }

                                unset($dp3reason_code_array);
                            } else {

                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reasons->reasonCode),
                                    array('TDESCRIPTION', $dp3_reasons->description),
                                    array('DDATETIME', date('Y-m-d H:i:s')),
                                );

                                if ($dp3_reasons->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }
                        }
                    }
                }
            } else {

                $additionalApplicantResults = $retrieveResponse->Rsp_IndividualCommercialResponse->response->additionalApplicantResults->additionalApplicantDecision;

                if (count($additionalApplicantResults) > 1) {

                    foreach ($additionalApplicantResults as $additionalApplicantResults_r) {

                        $dp3_reasons = $additionalApplicantResults_r->reasons;

                        if (count($dp3_reasons) > 1) {

                            if (!empty($dp3reason_code)) {

                                $dp3reason_code_array = array();

                                foreach ($dp3reason_code as $dp3reason_code_arr) {
                                    $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                }

                                foreach ($dp3_reasons as $dp3_reason) {
                                    if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                                        $dp3_reason_data = array(
                                            array('IPUBLISH', 1),
                                            array('VURL', sha1(rand())),
                                            array('IAPPLICATION_ID', $application_info->IID),
                                            array('VREASON_CODE', $dp3_reason->reasonCode),
                                            array('TDESCRIPTION', $dp3_reason->description),
                                            array('DDATETIME', date('Y-m-d H:i:s')),
                                        );

                                        if ($dp3_reason->reasonCode != '') {
                                            $Dp3reasoncodeModel->insert($dp3_reason_data);
                                        }
                                    }
                                }

                                unset($dp3reason_code_array);
                            } else {

                                foreach ($dp3_reasons as $dp3_reason) {

                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reason->reasonCode),
                                        array('TDESCRIPTION', $dp3_reason->description),
                                        array('DDATETIME', date('Y-m-d H:i:s')),
                                    );

                                    if ($dp3_reason->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }
                            }
                        } else {

                            if (!empty($dp3reason_code)) {

                                $dp3reason_code_array = array();

                                foreach ($dp3reason_code as $dp3reason_code_arr) {
                                    $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                                }

                                if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reasons->reasonCode),
                                        array('TDESCRIPTION', $dp3_reasons->description),
                                        array('DDATETIME', date('Y-m-d H:i:s')),
                                    );

                                    if ($dp3_reasons->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }

                                unset($dp3reason_code_array);
                            } else {

                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reasons->reasonCode),
                                    array('TDESCRIPTION', $dp3_reasons->description),
                                    array('DDATETIME', date('Y-m-d H:i:s')),
                                );

                                if ($dp3_reasons->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }
                        }
                    }
                } else {

                    $dp3_reasons = $additionalApplicantResults->reasons;

                    if (count($dp3_reasons) > 1) {

                        if (!empty($dp3reason_code)) {

                            $dp3reason_code_array = array();

                            foreach ($dp3reason_code as $dp3reason_code_arr) {
                                $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                            }

                            foreach ($dp3_reasons as $dp3_reason) {
                                if (!in_array($dp3_reason->reasonCode, $dp3reason_code_array)) {
                                    $dp3_reason_data = array(
                                        array('IPUBLISH', 1),
                                        array('VURL', sha1(rand())),
                                        array('IAPPLICATION_ID', $application_info->IID),
                                        array('VREASON_CODE', $dp3_reason->reasonCode),
                                        array('TDESCRIPTION', $dp3_reason->description),
                                        array('DDATETIME', date('Y-m-d H:i:s')),
                                    );

                                    if ($dp3_reason->reasonCode != '') {
                                        $Dp3reasoncodeModel->insert($dp3_reason_data);
                                    }
                                }
                            }

                            unset($dp3reason_code_array);
                        } else {

                            foreach ($dp3_reasons as $dp3_reason) {

                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reason->reasonCode),
                                    array('TDESCRIPTION', $dp3_reason->description),
                                    array('DDATETIME', date('Y-m-d H:i:s')),
                                );

                                if ($dp3_reason->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }
                        }
                    } else {

                        if (!empty($dp3reason_code)) {

                            $dp3reason_code_array = array();

                            foreach ($dp3reason_code as $dp3reason_code_arr) {
                                $dp3reason_code_array[] = $dp3reason_code_arr->VREASON_CODE;
                            }

                            if (!in_array($dp3_reasons->reasonCode, $dp3reason_code_array)) {
                                $dp3_reason_data = array(
                                    array('IPUBLISH', 1),
                                    array('VURL', sha1(rand())),
                                    array('IAPPLICATION_ID', $application_info->IID),
                                    array('VREASON_CODE', $dp3_reasons->reasonCode),
                                    array('TDESCRIPTION', $dp3_reasons->description),
                                    array('DDATETIME', date('Y-m-d H:i:s')),
                                );

                                if ($dp3_reasons->reasonCode != '') {
                                    $Dp3reasoncodeModel->insert($dp3_reason_data);
                                }
                            }

                            unset($dp3reason_code_array);
                        } else {

                            $dp3_reason_data = array(
                                array('IPUBLISH', 1),
                                array('VURL', sha1(rand())),
                                array('IAPPLICATION_ID', $application_info->IID),
                                array('VREASON_CODE', $dp3_reasons->reasonCode),
                                array('TDESCRIPTION', $dp3_reasons->description),
                                array('DDATETIME', date('Y-m-d H:i:s')),
                            );

                            if ($dp3_reasons->reasonCode != '') {
                                $Dp3reasoncodeModel->insert($dp3_reason_data);
                            }
                        }
                    }
                }
            }

            $res_data = array(
                'esisResponse' => $retrieveResponse->Rsp_IndividualCommercialResponse->response->esisResponse,
                'errorDetails' => $retrieveResponse->Rsp_IndividualCommercialResponse->errorDetails,
                'alertDetails' => $retrieveResponse->Rsp_IndividualCommercialResponse->alertResponses->alertResponse
            );

            return $res_data;
        }
    }

    /**
     * @prasanna
     * transfer owner of the application
     */
    public function transferMultipleOwner()
    {

        $perms = new UserPriviledgeModel($this->db);
        $userPriviledgeController = new UserPriviledgeController($this->db);

        if ($perms->check_access("TRANSFER_OWNER")) {

            $ApplicationController = new ApplicationController($this->db);
            $OwnertransferModel = new OwnertransferModel($this->db);

            $time_zone = check_submit_var($_POST['time_zone'], 'V', 0, 0, 1, '');
            $new_owner = check_submit_var($_POST['new_owner'], 'V', 0, 0, 1, '');
            $reason = check_submit_var($_POST['reason'], 'V', 0, 0, 1, '');
            $vurl_array = check_submit_var($_POST['vurl_array'], 'V', 0, 0, 1, '');

            $vurl_arr = explode(',', $vurl_array);

            if (!empty($vurl_arr)) {

                foreach ($vurl_arr as $app_vurl) {

                    $application_info = $ApplicationController->getApplicationByVURL($app_vurl);

                    if ($application_info) {

                        $ownertransfer_history = $OwnertransferModel->getOwnerTransferByAppID($application_info->IID);

                        if (!empty($ownertransfer_history)) {
                            $current_owner = $ownertransfer_history->ICURRENT_OWNER_ID;
                        } else {
                            $current_owner = 0;
                        }

                        $owner_vurl = sha1(rand());

                        $data = array();

                        array_push($data, array('VURL', $owner_vurl));
                        array_push($data, array('IAPPLICATION_ID', $application_info->IID));
                        array_push($data, array('ICURRENT_OWNER_ID', $new_owner));
                        array_push($data, array('IPREVIOUS_OWNER_ID', $current_owner));
                        array_push($data, array('ITRANSFERD_BY', $_SESSION['user_id']));
                        array_push($data, array('DTRANSFERED_DATE', date('Y-m-d H:i:s')));
                        array_push($data, array('TNOTES', $reason));
                        array_push($data, array('IPUBLISH', 1));

                        $response = array();

                        if ($new_owner) {
                            $res = $OwnertransferModel->insert($data);
                            if ($res) {

                                /**
                                 * Generate Note
                                 */
                                $NotesController = new NotesController($this->db);
                                $CompanyuserModel = new CompanyuserModel($this->db);

                                $from_user = 'System User';
                                $to_user = 'System User';
                                $by_user = 'System User';

                                if ($current_owner != 0) {
                                    $user1 = $CompanyuserModel->getUserNameByIID($current_owner);
                                    if ($user1) {
                                        $from_user = ucfirst($user1->VFIRST_NAME) . ' ' . ucfirst($user1->VLAST_NAME);
                                    }
                                }

                                if ($new_owner != 0) {
                                    $user2 = $CompanyuserModel->getUserNameByIID($new_owner);
                                    if ($user2) {
                                        $to_user = ucfirst($user2->VFIRST_NAME) . ' ' . ucfirst($user2->VLAST_NAME);
                                    }
                                }

                                if ($_SESSION['user_id'] != 0) {
                                    $user3 = $CompanyuserModel->getUserNameByIID($_SESSION['user_id']);
                                    if ($user3) {
                                        $by_user = ucfirst($user3->VFIRST_NAME) . ' ' . ucfirst($user3->VLAST_NAME);
                                    }
                                }

                                $converted_datetime = get_country_datetime($time_zone, date('Y-m-d H:i:s'), 'Y-m-d h:i:s A');
                                $note = 'Application Trasferred from ' . $from_user . ' to ' . $to_user . ' by ' . $by_user . ' on ' . $converted_datetime;

                                $data_note = array(
                                    'IAPPLICATION_ID' => $application_info->IID,
                                    'VCATEGORY' => 'case_manager',
                                    'VSECTION' => 'transfer',
                                    'IRECORDID' => $res,
                                    'TNOTE' => $note,
                                    'BY_ID' => $_SESSION['user_id']
                                );

                                $NotesController->generateNote($data_note);

                                // end of generate note
                                $response = array(
                                    'status' => 'success'
                                );
                            } else {
                                $response = array(
                                    'status' => 'error'
                                );
                            }
                        } else {
                            $response = array(
                                'status' => 'error'
                            );
                        }
                    } else {
                        $response = array(
                            'status' => 'error'
                        );
                    }
                }
            } else {
                $response = array(
                    'status' => 'error'
                );
            }

            echo json_encode($response);
        } else {
            $userPriviledgeController->access_denide();
        }
    }

    /**
     * @prasanna
     * transfer owner of the application
     */
    public function transferOwner()
    {

        $perms = new UserPriviledgeModel($this->db);
        $userPriviledgeController = new UserPriviledgeController($this->db);

        if ($perms->check_access("TRANSFER_OWNER")) {

            $ApplicationController = new ApplicationController($this->db);
            $OwnertransferModel = new OwnertransferModel($this->db);

            $time_zone = check_submit_var($_POST['time_zone'], 'V', 0, 0, 1, '');
            $current_owner = check_submit_var($_POST['current_owner'], 'V', 0, 0, 1, '');
            $new_owner = check_submit_var($_POST['new_owner'], 'V', 0, 0, 1, '');
            $reason = check_submit_var($_POST['reason'], 'V', 0, 0, 1, '');
            $app_vurl = check_submit_var($_POST['application_vurl'], 'V', 0, 0, 1, '');

            $application_info = $ApplicationController->getApplicationByVURL($app_vurl);

            if ($application_info) {

                $owner_vurl = sha1(rand());

                $data = array();

                array_push($data, array('VURL', $owner_vurl));
                array_push($data, array('IAPPLICATION_ID', $application_info->IID));
                array_push($data, array('ICURRENT_OWNER_ID', $new_owner));
                array_push($data, array('IPREVIOUS_OWNER_ID', $current_owner));
                array_push($data, array('ITRANSFERD_BY', $_SESSION['user_id']));
                array_push($data, array('DTRANSFERED_DATE', date('Y-m-d H:i:s')));
                array_push($data, array('TNOTES', $reason));
                array_push($data, array('IPUBLISH', 1));

                $response = array();

                if ($new_owner) {
                    $res = $OwnertransferModel->insert($data);
                    if ($res) {

                        /**
                         * Generate Note
                         */
                        $NotesController = new NotesController($this->db);
                        $CompanyuserModel = new CompanyuserModel($this->db);

                        $from_user = 'System User';
                        $to_user = 'System User';
                        $by_user = 'System User';

                        if ($current_owner != 0) {
                            $user1 = $CompanyuserModel->getUserNameByIID($current_owner);
                            if ($user1) {
                                $from_user = ucfirst($user1->VFIRST_NAME) . ' ' . ucfirst($user1->VLAST_NAME);
                            }
                        }

                        /*2019-09-19*/
                        $user2 = array();
                        /*2019-09-19*/

                        if ($new_owner != 0) {
                            $user2 = $CompanyuserModel->getUserNameByIID($new_owner);
                            if ($user2) {
                                $to_user = ucfirst($user2->VFIRST_NAME) . ' ' . ucfirst($user2->VLAST_NAME);
                            }
                        }

                        if ($_SESSION['user_id'] != 0) {
                            $user3 = $CompanyuserModel->getUserNameByIID($_SESSION['user_id']);
                            if ($user3) {
                                $by_user = ucfirst($user3->VFIRST_NAME) . ' ' . ucfirst($user3->VLAST_NAME);
                            }
                        }

                        $converted_datetime = get_country_datetime($time_zone, date('Y-m-d H:i:s'), 'Y-m-d h:i:s A');
                        $note = 'Application Trasferred from ' . $from_user . ' to ' . $to_user . ' by ' . $by_user . ' on ' . $converted_datetime;

                        $data_note = array(
                            'IAPPLICATION_ID' => $application_info->IID,
                            'VCATEGORY' => 'case_manager',
                            'VSECTION' => 'transfer',
                            'IRECORDID' => $res,
                            'TNOTE' => $note,
                            'BY_ID' => $_SESSION['user_id']
                        );

                        $NotesController->generateNote($data_note);

                        // end of generate note
                        $response = array(
                            'status' => 'success'
                        );

                        /*2019-09-19*/
                        if (!empty($user2)) {
                            $response['current_owner_username'] = $user2->VUSER_NAME;
                            $response['current_owner_iid'] = $user2->IID;
                        }
                        /*2019-09-19*/

                        /*owner transfer history*/
                        $ownertransfer_info = $OwnertransferModel->getOwnerTransferHistory($application_info->IID);

                        $data_summary = array(
                            'ownertransfer_info' => $ownertransfer_info,
                            'time_zone' => $time_zone
                        );

                        $view_owner_transfer_history = 'views/manage_application/sub_manage_application/sub_summary/owner_transfer_history';

                        $res_ownertransfer_history = $this->loadHtmlView($view_owner_transfer_history, $data_summary);
                        if (!empty($res_ownertransfer_history)) {
                            $response['res_ownertransfer_history_html'] = $res_ownertransfer_history;
                        }
                        /*end of owner transfer history*/
                        /*notes*/
                        $notesModel = new NotesModel($this->db);
                        $casemanager_notes = $notesModel->getAllNotesByAppIdVcategory($application_info->IID, 'case_manager', 1);

                        $new_notes_count = 0;

                        foreach ($casemanager_notes as $cm_note_r) {
                            if (empty($cm_note_r->IREAD) || $cm_note_r->IREAD == 0) {
                                $new_notes_count++;
                            }
                        }

                        $data_notes = array(
                            'casemanager_notes' => $casemanager_notes,
                            'time_zone' => $time_zone
                        );

                        $view_notes = 'views/manage_application/sub_manage_application/notes';

                        $res_notes = $this->loadHtmlView($view_notes, $data_notes);
                        if (!empty($res_notes)) {
                            $response['res_notes_html'] = $res_notes;
                            $response['res_new_notes_count'] = $new_notes_count;
                        }
                        /*end of notes*/
                    } else {
                        $response = array(
                            'status' => 'error'
                        );
                    }
                } else {
                    $response = array(
                        'status' => 'error'
                    );
                }
            } else {
                $response = array(
                    'status' => 'error'
                );
            }

            echo json_encode($response);
        } else {
            $userPriviledgeController->access_denide();
        }
    }

    /**
     * heshani
     * get company summery details
     * @param $fullURLfront
     */
    function getCompanySummery()
    {
        $fullURLfront = $this->fullURLfront;

        $perms = new UserPriviledgeModel($this->db);
        $userPriviledgeController = new UserPriviledgeController($this->db);

        if ($perms->check_access("DISPLAY_APPLICATION_SUMMARY")) {
            $companyModel = new CompanyModel($this->db);

            $company_id = "";
            if ($_SESSION['user_role'] == "USER") {
                $company_id = $_SESSION['company_id'];
            }

            /*get company details*/
            $data['companies'] = $companyModel->getAllCompanies($company_id);
            $data['db'] = $this->db;
            $data['fullURLfront'] = $fullURLfront;
            $data['caseManagerURL'] = $this->caseManagerURL;

            /*display_element_array*/
            $cmSetupScreenModel = new CmSetupScreenModel();
            $data['cmSetupScreenModel'] = $cmSetupScreenModel;
            $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();
            $data['cmSetupScreenInfoModel'] = $cmSetupScreenInfoModel;
            /*end of display_element_array*/

            /* application search start */
            $func_app_search = check_submit_var($_REQUEST['func_app_search'], 'V', 0, 0, 1, '');

            if (isset($func_app_search) && !empty($func_app_search)) {

                $smartSignatureModel = new SmartSignatureModel($this->db);
                $data['smartSignatureModel'] = $smartSignatureModel;

                $directorModel = new DirectorModel($this->db);
                $data['directorModel'] = $directorModel;

                $pagination_orderby = "APP.IDP3_APPLICATION_ID DESC";

                $applicationModel = new ApplicationModel($this->db);

                $publish_status = 1;

                $date_from = "";
                if (isset($_POST["date_from"])) {
                    $date_from = check_submit_var($_POST["date_from"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_date_from'] = $date_from;
                } else {
                    if (isset($_SESSION['temp_ses_date_from'])) {
                        $date_from = $_SESSION['temp_ses_date_from'];
                        $_REQUEST['date_from'] = $date_from;
                    }
                }

                $date_to = "";
                if (isset($_POST["date_to"])) {
                    $date_to = check_submit_var($_POST["date_to"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_date_to'] = $date_to;
                } else {
                    if (isset($_SESSION['temp_ses_date_to'])) {
                        $date_to = $_SESSION['temp_ses_date_to'];
                        $_REQUEST['date_to'] = $date_to;
                    }
                }

                $BN_value = "";
                $ACN_value = "";
                if (isset($_POST["BN_value"])) {
                    $BN_value = check_submit_var($_POST["BN_value"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_BN_value'] = $BN_value;
                    if (strlen($BN_value) == 9) {
                        $ACN_value = $BN_value;
                        $BN_value = "";
                    }
                } else {
                    if (isset($_SESSION['temp_ses_BN_value'])) {
                        $BN_value = $_SESSION['temp_ses_BN_value'];
                        $_REQUEST['BN_value'] = $BN_value;
                        if (strlen($BN_value) == 9) {
                            $ACN_value = $BN_value;
                            $BN_value = "";
                        }
                    }
                }

                $processing_status = "";
                if (isset($_POST["processing_status"])) {
                    $processing_status = check_submit_var($_POST["processing_status"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_processing_status'] = $processing_status;
                } else {
                    if (isset($_SESSION['temp_ses_processing_status'])) {
                        $processing_status = $_SESSION['temp_ses_processing_status'];
                        $_REQUEST['processing_status'] = $processing_status;
                    }
                }

                $dp3_decision = "";
                if (isset($_POST["dp3_decision"])) {
                    $dp3_decision = check_submit_var($_POST["dp3_decision"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_dp3_decision'] = $dp3_decision;
                } else {
                    if (isset($_SESSION['temp_ses_dp3_decision'])) {
                        $dp3_decision = $_SESSION['temp_ses_dp3_decision'];
                        $_REQUEST['dp3_decision'] = $dp3_decision;
                    }
                }

                $process_tasks_status = "";
                if (isset($_POST["process_tasks_status"])) {
                    $process_tasks_status = check_submit_var($_POST["process_tasks_status"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_process_tasks_status'] = $process_tasks_status;
                } else {
                    if (isset($_SESSION['temp_ses_process_tasks_status'])) {
                        $process_tasks_status = $_SESSION['temp_ses_process_tasks_status'];
                        $_REQUEST['process_tasks_status'] = $process_tasks_status;
                    }
                }

                $dp3_status = "";

                $other_status = "";
                if (isset($_POST["other_status"])) {
                    $other_status = check_submit_var($_POST["other_status"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_other_status'] = $other_status;
                } else {
                    if (isset($_SESSION['temp_ses_other_status'])) {
                        $other_status = $_SESSION['temp_ses_other_status'];
                        $_REQUEST['other_status'] = $other_status;
                    }
                }

                $application_id = "";
                if (isset($_POST["dp3_application_id"])) {
                    $application_id = check_submit_var($_POST["dp3_application_id"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_dp3_application_id'] = $application_id;
                } else {
                    if (isset($_SESSION['temp_ses_dp3_application_id'])) {
                        $dp3_application_id = $_SESSION['temp_ses_dp3_application_id'];
                        $_REQUEST['dp3_application_id'] = $application_id;
                    }
                }

                $company_reg_no = "";

                $organization_name = "";
                if (isset($_POST["organization_name"])) {
                    $organization_name = check_submit_var($_POST["organization_name"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_organization_name'] = $organization_name;
                } else {
                    if (isset($_SESSION['temp_ses_organization_name'])) {
                        $organization_name = $_SESSION['temp_ses_organization_name'];
                        $_REQUEST['organization_name'] = $organization_name;
                    }
                }

                $trading_name = "";
                if (isset($_POST["trading_name"])) {
                    $trading_name = check_submit_var($_POST["trading_name"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_trading_name'] = $trading_name;
                } else {
                    if (isset($_SESSION['temp_ses_trading_name'])) {
                        $trading_name = $_SESSION['temp_ses_trading_name'];
                        $_REQUEST['trading_name'] = $trading_name;
                    }
                }

                $individual_name = "";
                if (isset($_POST["individual_name"])) {
                    $individual_name = check_submit_var($_POST["individual_name"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_individual_name'] = $individual_name;
                } else {
                    if (isset($_SESSION['temp_ses_individual_name'])) {
                        $individual_name = $_SESSION['temp_ses_individual_name'];
                        $_REQUEST['individual_name'] = $individual_name;
                    }
                }

                $smart_signature_status = "";
                if (isset($_POST["smart_signature_status"])) {
                    $smart_signature_status = check_submit_var($_POST["smart_signature_status"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_smart_signature_status'] = $smart_signature_status;
                } else {
                    if (isset($_SESSION['temp_ses_smart_signature_status'])) {
                        $smart_signature_status = $_SESSION['temp_ses_smart_signature_status'];
                        $_REQUEST['smart_signature_status'] = $smart_signature_status;
                    }
                }

                $owner_id = "";
                if (isset($_POST["owner_id"])) {
                    $owner_id = check_submit_var($_POST["owner_id"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_owner_id'] = $owner_id;
                } else {
                    if (isset($_SESSION['temp_ses_owner_id'])) {
                        $owner_id = $_SESSION['temp_ses_owner_id'];
                        $_REQUEST['owner_id'] = $owner_id;
                    }
                }

                $app_account_no = "";
                if (isset($_POST["app_account_no"])) {
                    $app_account_no = check_submit_var($_POST["app_account_no"], 'V', 0, 0, 1, '');
                    $_SESSION['temp_ses_app_account_no'] = $app_account_no;
                } else {
                    if (isset($_SESSION['temp_ses_app_account_no'])) {
                        $app_account_no = $_SESSION['temp_ses_app_account_no'];
                        $_REQUEST['app_account_no'] = $app_account_no;
                    }
                }

                $data['fullURLfront'] = $fullURLfront;
                $data['db'] = $this->db;

                $country_code = 'AU';
                $time_zone = 'Australia/Melbourne';
                $hour = 10;

                if (!empty($data['companies'][0]['COUNTRY_CODE'])) {
                    $country_code = $data['companies'][0]['COUNTRY_CODE'];
                    $time_zone = 'Australia/Melbourne';
                    $hour = 10;
                    if ($country_code == "NZ") {
                        $time_zone = 'Pacific/Auckland';
                        $hour = 12;
                    }
                }

                $data['time_zone'] = $time_zone;

                if (!empty($date_from)) {
                    $date_from_gmt = date('Y-m-d H:i:s', strtotime($date_from . '-' . $hour . 'hours'));
                }
                if (!empty($date_to)) {
                    $date_to_gmt = date('Y-m-d H:i:s', strtotime($date_to . '+' . (24 - $hour) . 'hours'));
                }

                $data['comny_tz_date'] = get_country_datetime($time_zone, date("Y-m-d H:i:s"), 'Y-m-d');

                /*pagination*/
                $pagination = check_submit_var($_REQUEST['pagination'], 'V', 0, 0, 1, '');

                $data['input_old'] = (object) $_REQUEST;

                $start = 1;
                $limit = 5;
                $display_pages = 10;

                if (!$pagination) {
                    $pagination = 1;
                    $start = 1;
                }

                if ($pagination > 1) {
                    $start = (($pagination - 1) * $limit) + 1;
                }

                $data['records_limit'] = $limit;
                $data['pagination_no'] = $pagination;

                $list_count = $applicationModel->getAllCompanyApplications($publish_status, '', $pagination, $pagination_orderby, $date_from_gmt, $date_to_gmt, $BN_value, $ACN_value, $country_code, $processing_status, $dp3_decision, $dp3_status, $process_tasks_status, $other_status, $application_id, $company_reg_no, $organization_name, $trading_name, $individual_name, $smart_signature_status, $owner_id, $app_account_no, 0, 0);

                $data['res_applications'] = $applicationModel->getAllCompanyApplications($publish_status, '', $pagination, $pagination_orderby, $date_from_gmt, $date_to_gmt, $BN_value, $ACN_value, $country_code, $processing_status, $dp3_decision, $dp3_status, $process_tasks_status, $other_status, $application_id, $company_reg_no, $organization_name, $trading_name, $individual_name, $smart_signature_status, $owner_id, $app_account_no, $limit, $start);

                $data['pagination_html'] = Pagination::make($pagination, $list_count->RECORD_COUNT, $limit, $display_pages, $this->fullURLfront . "/applications/manage/list.html?func_app_search=1", 1);
                /*end of pagination*/
            }
            /* application search end */

            $view = 'views/manage_application/applications_manage_summery';

            $this->loadView($view, $data);
        } else {
            $userPriviledgeController->access_denide();
        }
    }

    /**
     * heshani
     * get applicant details for company
     * @param $fullURLfront
     */
    function getCompanyApplicants()
    {
        $fullURLfront = $this->fullURLfront;

        $perms = new UserPriviledgeModel($this->db);
        $userPriviledgeController = new UserPriviledgeController($this->db);
        $companymasterotherModel = new CompanymasterotherModel($this->db);

        if ($perms->check_access("DISPLAY_COMPANY_APPLICATIONS")) {

            $c = new App_Sandbox_Cipher(PW_KEY);
            $data['en_de'] = $c;
            $en_pagination = check_submit_var($_REQUEST["pagination"], 'V', 0, 0, 1, '');
            $pagination = $en_pagination;

            $en_orderby = check_submit_var($_REQUEST["pagingorder"], 'V', 0, 0, 1, '');

            if ($en_orderby != "") {
                $pagination_orderby = $c->decrypt($en_orderby);
            } else {
                $pagination_orderby = "APP.DSUBMITTED_DATE ASC, APP.IDP3_APPLICATION_ID ASC";
            }

            /*2019-08-06*/
            /*for submitted applications*/
            $en_app_progress_status = check_submit_var($_REQUEST["app_status"], 'V', 0, 0, 1, '');
            $app_progress_status = $c->decrypt($en_app_progress_status);
            $data['app_status'] = $app_progress_status;
            $data['en_app_status'] = $en_app_progress_status;
            if ($app_progress_status == "COMPLETED") {
                $_SESSION['ses_display_method'] = "SEARCH";
                if (!isset($_SESSION['ses_processing_status'])) {
                    $_SESSION['ses_processing_status'] = "SUBMITTED";
                }
                if (!isset($_SESSION['ses_process_tasks_status'])) {
                    $_SESSION['ses_process_tasks_status'] = "PENDING";
                }
            } else if ($app_progress_status == "SAVED") {
                $_SESSION['ses_display_method'] = "SEARCH";
                if (!isset($_SESSION['ses_process_tasks_status'])) {
                    $_SESSION['ses_process_tasks_status'] = "PENDING";
                }
            }
            /*2019-08-06*/

            $applicationModel = new ApplicationModel($this->db);
            $companyModel = new CompanyModel($this->db);

            $data['fullURLfront'] = $fullURLfront;
            $data['db'] = $this->db;
            $data['owner_apps'] = "";

            $en_company_id = check_submit_var($_REQUEST["company"], 'V', 0, 0, 1, '');
            $company_id = $c->decrypt($en_company_id);
            $data['en_company_id'] = $en_company_id;

            $en_app_type = check_submit_var($_REQUEST["app_type"], 'V', 0, 0, 1, '');
            $app_type = $c->decrypt($en_app_type);
            $data['app_type'] = $app_type;
            $data['app_type_en'] = $en_app_type;

            $publish_status = 1;

            //get company details
            $data['company'] = $companyModel->getAllCompanies($company_id);

            $country_code = $data['company'][0]['COUNTRY_CODE'];
            $time_zone = 'Australia/Melbourne';
            $hour = 10;
            if ($country_code == "NZ") {
                $time_zone = 'Pacific/Auckland';
                $hour = 12;
            }
            $data['comny_tz_date'] = get_country_datetime($time_zone, date("Y-m-d H:i:s"), 'Y-m-d');

            $CompanyuserModel = new CompanyuserModel($this->db);
            $data['company_users'] = $CompanyuserModel->getUsersByComID("$company_id,0", "", "25");

            $data['credit_amt_multiplier'] = $companymasterotherModel->getComMasterOtherByComIdByVtype($company_id, 'credit_amt_multiplier');

            if (isset($_SESSION['ses_display_method'])) {
                if ($_SESSION['ses_display_method'] == "SEARCH") {

                    $date_from = $_SESSION['ses_date_from'];
                    $date_to = $_SESSION['ses_date_to'];
                    $BN_value = $_SESSION['ses_BN_value'];
                    $application_id = $_SESSION['ses_application_id'];
                    $dp3_status = $_SESSION['ses_dp3_status'];
                    $process_tasks_status = $_SESSION['ses_process_tasks_status'];
                    $dp3_decision = $_SESSION['ses_dp3_decision'];
                    $processing_status = $_SESSION['ses_processing_status'];
                    $other_status = $_SESSION['ses_other_status'];
                    $country_code = $_SESSION['ses_country_code'];
                    $company_reg_no = $_SESSION['ses_company_reg_no'];
                    $organization_name = $_SESSION['ses_organization_name'];
                    $trading_name = $_SESSION['ses_trading_name'];
                    $individual_name = $_SESSION['ses_individual_name'];
                    $smart_signature_status = $_SESSION['ses_smart_signature_status'];
                    $owner_id = $_SESSION['owner_id'];
                    $app_account_no = $_SESSION['app_account_no'];

                    $_SESSION['ses_display_method'] = "SEARCH";

                    /*if (!empty($date_from)) {
                        $date_from_gmt = get_gmt_datetime($date_from, 'Y-m-d');
                    }
                    if (!empty($date_to)) {
                        $date_to_gmt = get_gmt_datetime($date_to, 'Y-m-d');
                    }*/

                    if (!empty($date_from)) {
                        $date_from_gmt = date('Y-m-d H:i:s', strtotime($date_from . '-' . $hour . 'hours'));
                    }
                    if (!empty($date_to)) {
                        $date_to_gmt = date('Y-m-d H:i:s', strtotime($date_to . '+' . (24 - $hour) . 'hours'));
                    }

                    $data['applicant_list'] = $applicationModel->getApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status, $pagination, $pagination_orderby, $date_from_gmt, $date_to_gmt, $BN_value, $country_code, $processing_status, $dp3_decision, $dp3_status, $process_tasks_status, $other_status, $application_id, $company_reg_no, $organization_name, $trading_name, $individual_name, $smart_signature_status, $owner_id, $app_account_no);
                    $data['display_method='] = "SEARCH";
                    $data['search_count'] = $applicationModel->getCountApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status, $date_from_gmt, $date_to_gmt, $BN_value, $country_code, $processing_status, $dp3_decision, $dp3_status, $process_tasks_status, $other_status, $application_id, $company_reg_no, $organization_name, $trading_name, $individual_name, $smart_signature_status, $owner_id, $app_account_no);
                } else {
                    //get company applications
                    $data['applicant_list'] = $applicationModel->getApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status, $pagination, $pagination_orderby);
                    $data['all_count'] = $applicationModel->getCountApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status);
                    $data['display_method='] = "ALL";
                    //$_SESSION['ses_display_method'] = "ALL";
                }
            } else {
                //get company applications
                $data['applicant_list'] = $applicationModel->getApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status, $pagination, $pagination_orderby);
                $data['all_count'] = $applicationModel->getCountApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status);
                $data['display_method='] = "ALL";
                //$_SESSION['ses_display_method'] = "ALL";
            }

            if ($app_type == "company") {
                //display_element_array
                $display_element_array = array();

                $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();
                $cmSetupScreenModel = new CmSetupScreenModel();

                $res_cm_setup = $cmSetupScreenModel->getScreenByVALIASComID("COMMERCIAL_APPLICATIONS_LIST_VIEW", $company_id);
                if ($res_cm_setup) {
                    $res_cm_setup_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup->IID);
                    if ($res_cm_setup_info) {
                        foreach ($res_cm_setup_info as $res_cm_setup_inf) {
                            array_push($display_element_array, $res_cm_setup_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array'] = $display_element_array;
                //end of display_element_array

                //display_element_array_search
                $display_element_array_search = array();

                $res_cm_setup_search = $cmSetupScreenModel->getScreenByVALIASComID("COMMERCIAL_APPLICATIONS_SEARCH_FILTER", $company_id);
                if ($res_cm_setup_search) {
                    $res_cm_setup_search_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup_search->IID);
                    if ($res_cm_setup_search_info) {
                        foreach ($res_cm_setup_search_info as $res_cm_setup_search_inf) {
                            array_push($display_element_array_search, $res_cm_setup_search_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array_search'] = $display_element_array_search;
                //end of display_element_array_search
            } else if ($app_type == "individual") {
                //display_element_array
                $display_element_array = array();

                $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();
                $cmSetupScreenModel = new CmSetupScreenModel();

                $res_cm_setup = $cmSetupScreenModel->getScreenByVALIASComID("INDIVIDUAL_APPLICATIONS_LIST_VIEW", $company_id);
                if ($res_cm_setup) {
                    $res_cm_setup_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup->IID);
                    if ($res_cm_setup_info) {
                        foreach ($res_cm_setup_info as $res_cm_setup_inf) {
                            array_push($display_element_array, $res_cm_setup_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array'] = $display_element_array;
                //end of display_element_array

                //display_element_array_search
                $display_element_array_search = array();

                $res_cm_setup_search = $cmSetupScreenModel->getScreenByVALIASComID("INDIVIDUAL_APPLICATIONS_SEARCH_FILTER", $company_id);
                if ($res_cm_setup_search) {
                    $res_cm_setup_search_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup_search->IID);
                    if ($res_cm_setup_search_info) {
                        foreach ($res_cm_setup_search_info as $res_cm_setup_search_inf) {
                            array_push($display_element_array_search, $res_cm_setup_search_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array_search'] = $display_element_array_search;
                //end of display_element_array_search
            }

            $view = 'views/manage_application/company_application_dashboard';

            $this->loadView($view, $data);
        } else {
            $userPriviledgeController->access_denide();
        }
    }

    /**
     * heshani
     * search applicant details for company
     * @param $fullURLfront
     */
    function search_company_applicants()
    {

        $fullURLfront = $this->fullURLfront;

        $perms = new UserPriviledgeModel($this->db);
        $userPriviledgeController = new UserPriviledgeController($this->db);

        if ($perms->check_access("SEARCH_APPLICATIONS")) {

            $c = new App_Sandbox_Cipher(PW_KEY);

            $en_pagination = check_submit_var($_REQUEST["pagination"], 'V', 0, 0, 1, '');
            $pagination = $en_pagination;

            $en_orderby = check_submit_var($_REQUEST["pagingorder"], 'V', 0, 0, 1, '');

            if ($en_orderby != "") {
                $pagination_orderby = $c->decrypt($en_orderby);
            } else {
                $pagination_orderby = "APP.DSUBMITTED_DATE ASC, APP.IDP3_APPLICATION_ID ASC";
            }

            $applicationModel = new ApplicationModel($this->db);
            $companyModel = new CompanyModel($this->db);
            $companymasterotherModel = new CompanymasterotherModel($this->db);

            $en_company_id = check_submit_var($_POST["company"], 'V', 0, 0, 1, '');
            $company_id = $c->decrypt($en_company_id);
            $data['en_company_id'] = $en_company_id;

            $app_type = check_submit_var($_POST["app_type"], 'V', 0, 0, 1, '');
            $data['app_type'] = $app_type;
            $data['app_type_en'] = $c->encrypt($app_type);

            $en_app_status = check_submit_var($_POST["en_app_status"], 'V', 0, 0, 1, '');
            $app_progress_status = $c->decrypt($en_app_status);
            $data['app_status'] = $app_progress_status;
            $data['en_app_status'] = $en_app_status;

            //            if ($app_type == 'company') {
            //                 $data['app_type'] = $c->encrypt($app_type);
            //            } else {
            //                $data['app_type'] = $app_type;
            //            }

            $publish_status = 1;

            $date_from = check_submit_var($_POST["date_from"], 'V', 0, 0, 1, '');
            $date_to = check_submit_var($_POST["date_to"], 'V', 0, 0, 1, '');
            $BN_value = check_submit_var($_POST["BN_value"], 'V', 0, 0, 1, '');
            $country_code = check_submit_var($_POST["country_code"], 'V', 0, 0, 1, '');
            $processing_status = check_submit_var($_POST["processing_status"], 'V', 0, 0, 1, '');
            $dp3_decision = check_submit_var($_POST["dp3_decision"], 'V', 0, 0, 1, '');
            $process_tasks_status = check_submit_var($_POST["process_tasks_status"], 'V', 0, 0, 1, '');
            $dp3_status = check_submit_var($_POST["dp3_status"], 'V', 0, 0, 1, '');
            $other_status = check_submit_var($_POST["other_status"], 'V', 0, 0, 1, '');
            $application_id = check_submit_var($_POST["dp3_application_id"], 'V', 0, 0, 1, '');
            $company_reg_no = check_submit_var($_POST["company_reg_no"], 'V', 0, 0, 1, '');
            $organization_name = check_submit_var($_POST["organization_name"], 'V', 0, 0, 1, '');
            $trading_name = check_submit_var($_POST["trading_name"], 'V', 0, 0, 1, '');
            $individual_name = check_submit_var($_POST["individual_name"], 'V', 0, 0, 1, '');
            $smart_signature_status = check_submit_var($_POST["smart_signature_status"], 'V', 0, 0, 1, '');
            $owner_id = check_submit_var($_POST["owner_id"], 'V', 0, 0, 1, '');
            $app_account_no = check_submit_var($_POST["app_account_no"], 'V', 0, 0, 1, '');

            /*if (!empty($date_from)) {
                $date_from_gmt = get_gmt_datetime($date_from, 'Y-m-d');
            }
            if (!empty($date_to)) {
                $date_to_gmt = get_gmt_datetime($date_to, 'Y-m-d');
            }*/

            $_SESSION['ses_date_from'] = $date_from;
            $_SESSION['ses_date_to'] = $date_to;
            $_SESSION['ses_BN_value'] = $BN_value;
            $_SESSION['ses_application_id'] = $application_id;
            $_SESSION['ses_process_tasks_status'] = $process_tasks_status;
            $_SESSION['ses_dp3_status'] = $dp3_status;
            $_SESSION['ses_dp3_decision'] = $dp3_decision;
            $_SESSION['ses_processing_status'] = $processing_status;
            $_SESSION['ses_other_status'] = $other_status;
            $_SESSION['ses_country_code'] = $country_code;
            $_SESSION['ses_company_reg_no'] = $company_reg_no;
            $_SESSION['ses_organization_name'] = $organization_name;
            $_SESSION['ses_trading_name'] = $trading_name;
            $_SESSION['ses_individual_name'] = $individual_name;
            $_SESSION['ses_smart_signature_status'] = $smart_signature_status;
            $_SESSION['owner_id'] = $owner_id;
            $_SESSION['app_account_no'] = $app_account_no;

            $_SESSION['ses_display_method'] = "SEARCH";


            $data['fullURLfront'] = $fullURLfront;
            $data['db'] = $this->db;
            $data['owner_apps'] = $other_status;


            //get company details
            $data['company'] = $companyModel->getAllCompanies($company_id);

            $country_code = $data['company'][0]['COUNTRY_CODE'];
            $time_zone = 'Australia/Melbourne';
            $hour = 10;
            if ($country_code == "NZ") {
                $time_zone = 'Pacific/Auckland';
                $hour = 12;
            }

            if (!empty($date_from)) {
                $date_from_gmt = date('Y-m-d H:i:s', strtotime($date_from . '-' . $hour . 'hours'));
            }
            if (!empty($date_to)) {
                $date_to_gmt = date('Y-m-d H:i:s', strtotime($date_to . '+' . (24 - $hour) . 'hours'));
            }

            $data['comny_tz_date'] = get_country_datetime($time_zone, date("Y-m-d H:i:s"), 'Y-m-d');

            $data['credit_amt_multiplier'] = $companymasterotherModel->getComMasterOtherByComIdByVtype($company_id, 'credit_amt_multiplier');

            //get company applications
            $data['applicant_list'] = $applicationModel->getApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status, $pagination, $pagination_orderby, $date_from_gmt, $date_to_gmt, $BN_value, $country_code, $processing_status, $dp3_decision, $dp3_status, $process_tasks_status, $other_status, $application_id, $company_reg_no, $organization_name, $trading_name, $individual_name, $smart_signature_status, $owner_id, $app_account_no);
            $data['display_method='] = "SEARCH";
            $data['search_count'] = $applicationModel->getCountApplicantsForCompany($company_id, $publish_status, $app_type, $app_progress_status, $date_from_gmt, $date_to_gmt, $BN_value, $country_code, $processing_status, $dp3_decision, $dp3_status, $process_tasks_status, $other_status, $application_id, $company_reg_no, $organization_name, $trading_name, $individual_name, $smart_signature_status, $owner_id, $app_account_no);

            //$data['applicant_list'] = $applicationModel->getApplicantsForCompany($company_id, $publish_status, $app_type, $pagination, $pagination_orderby);

            //display_element_array
            /*$display_element_array = array();

            $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();
            $cmSetupScreenModel = new CmSetupScreenModel();

            $res_cm_setup = $cmSetupScreenModel->getScreenByVALIASComID("COMMERCIAL_APPLICATIONS_LIST_VIEW", $company_id);
            if ($res_cm_setup) {
                $res_cm_setup_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup->IID);
                if ($res_cm_setup_info) {
                    foreach ($res_cm_setup_info as $res_cm_setup_inf) {
                        array_push($display_element_array, $res_cm_setup_inf->VALIAS);
                    }
                }
            }
            $data['display_element_array'] = $display_element_array;*/
            //end of display_element_array

            /* ########################### */
            if ($app_type == "company") {
                //display_element_array
                $display_element_array = array();

                $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();
                $cmSetupScreenModel = new CmSetupScreenModel();

                $res_cm_setup = $cmSetupScreenModel->getScreenByVALIASComID("COMMERCIAL_APPLICATIONS_LIST_VIEW", $company_id);
                if ($res_cm_setup) {
                    $res_cm_setup_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup->IID);
                    if ($res_cm_setup_info) {
                        foreach ($res_cm_setup_info as $res_cm_setup_inf) {
                            array_push($display_element_array, $res_cm_setup_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array'] = $display_element_array;
                //end of display_element_array

                //display_element_array_search
                $display_element_array_search = array();

                $res_cm_setup_search = $cmSetupScreenModel->getScreenByVALIASComID("COMMERCIAL_APPLICATIONS_SEARCH_FILTER", $company_id);
                if ($res_cm_setup_search) {
                    $res_cm_setup_search_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup_search->IID);
                    if ($res_cm_setup_search_info) {
                        foreach ($res_cm_setup_search_info as $res_cm_setup_search_inf) {
                            array_push($display_element_array_search, $res_cm_setup_search_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array_search'] = $display_element_array_search;
                //end of display_element_array_search
            } else if ($app_type == "individual") {
                //display_element_array
                $display_element_array = array();

                $cmSetupScreenInfoModel = new CmSetupScreenInfoModel();
                $cmSetupScreenModel = new CmSetupScreenModel();

                $res_cm_setup = $cmSetupScreenModel->getScreenByVALIASComID("INDIVIDUAL_APPLICATIONS_LIST_VIEW", $company_id);
                if ($res_cm_setup) {
                    $res_cm_setup_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup->IID);
                    if ($res_cm_setup_info) {
                        foreach ($res_cm_setup_info as $res_cm_setup_inf) {
                            array_push($display_element_array, $res_cm_setup_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array'] = $display_element_array;
                //end of display_element_array

                //display_element_array_search
                $display_element_array_search = array();

                $res_cm_setup_search = $cmSetupScreenModel->getScreenByVALIASComID("INDIVIDUAL_APPLICATIONS_SEARCH_FILTER", $company_id);
                if ($res_cm_setup_search) {
                    $res_cm_setup_search_info = $cmSetupScreenInfoModel->getAllDisplayScreenInfoByScreenID($res_cm_setup_search->IID);
                    if ($res_cm_setup_search_info) {
                        foreach ($res_cm_setup_search_info as $res_cm_setup_search_inf) {
                            array_push($display_element_array_search, $res_cm_setup_search_inf->VALIAS);
                        }
                    }
                }
                $data['display_element_array_search'] = $display_element_array_search;
                //end of display_element_array_search
            }
            /* ########################### */

            $view = "views/manage_application/company_application_dashboard_search";

            return $this->loadView($view, $data);
        } else {
            $userPriviledgeController->access_denide();
        }
    }

    public function updateProcessStatus()
    {
        $perms = new UserPriviledgeModel($this->db);
        $userPriviledgeController = new UserPriviledgeController($this->db);

        if ($perms->check_access("CHANGE_STATUSES")) {

            $cur_datetime = date("Y-m-d H:i:s");

            //update process task status
            $status_id = check_submit_var($_POST["status_id"], 'V', 0, 0, 1, '');
            $task_id = check_submit_var($_POST["task_id_process_task"], 'V', 0, 0, 1, '');
            $application_id = check_submit_var($_POST["application_id"], 'V', 0, 0, 1, '');
            $trade_ref = check_submit_var($_POST["trade_ref"], 'V', 0, 0, 1, '');
            $del_approval = check_submit_var($_POST["del_approval"], 'V', 0, 0, 1, '');
            $signed_application = check_submit_var($_POST["signed_application"], 'V', 0, 0, 1, '');
            $vurl_task = sha1(rand());

            $processTaskModel = new ProcessTaskModel($this->db);

            if (!empty($task_id)) {
                //update

                $taks_fields[] = array('VTRADE_REF', $trade_ref);
                $taks_fields[] = array('VDELEGATED_APPROVAL', $del_approval);
                $taks_fields[] = array('VSIGNED_APPLICATION', $signed_application);
                $taks_fields[] = array('IUPDATEDBY', $_SESSION['user_id']);
                $taks_fields[] = array('DUPDATED_DATE', $cur_datetime);

                /* if ($trade_ref == "COMPLETED" && $del_approval == "COMPLETED" && $signed_application == "COMPLETED") {
                  $taks_fields[] = array('VPROCESS_TASKS_STATUS', "COMPLETED");
                  } else {
                  $taks_fields[] = array('VPROCESS_TASKS_STATUS', "PENDING");
                  } */
                $taks_fields[] = array('IPUBLISH', "1");

                $msg = $processTaskModel->updateProcessTask($taks_fields, $task_id);
            } else {
                //insert

                $taks_fields[] = array('VURL', $vurl_task);
                $taks_fields[] = array('IAPPLICATION_ID', $application_id);
                $taks_fields[] = array('VTRADE_REF', $trade_ref);
                $taks_fields[] = array('VDELEGATED_APPROVAL', $del_approval);
                $taks_fields[] = array('VSIGNED_APPLICATION', $signed_application);
                $taks_fields[] = array('VPROCESS_TASKS_STATUS', "PENDING");
                $taks_fields[] = array('IADDEDBY', $_SESSION['user_id']);
                $taks_fields[] = array('DADDED_DATE', $cur_datetime);

                $taks_fields[] = array('IPUBLISH', "1");

                $task_id = $processTaskModel->saveProcessTask($taks_fields);
            }

            /* if ($trade_ref != "PENDING" && $del_approval != "PENDING" && $signed_application != "PENDING") {
              $appstatusModel = new AppstatusModel($this->db);

              $data_fields[] = array('VPROCESS_TASK_STATUS', "COMPLETED");
              $data_fields[] = array('VPROCESSING_STATUS', "COMPLETED");
              $data_fields[] = array('IUPDATED_BY', $_SESSION['user_id']);
              $data_fields[] = array('DUPDATED_DATETIME', $cur_datetime);

              $msg = $appstatusModel->updateProcessStatus($data_fields, $status_id);
              } */

            echo $task_id;
        } else {
            $userPriviledgeController->access_denide();
        }
    }

    function complete_process_task()
    {

        $cur_datetime = date("Y-m-d H:i:s");

        //update process task status
        $time_zone = check_submit_var($_POST["time_zone"], 'V', 0, 0, 1, '');
        $status_id = check_submit_var($_POST["status_id"], 'V', 0, 0, 1, '');
        $task_id = check_submit_var($_POST["task_id_process_task"], 'V', 0, 0, 1, '');
        $application_id = check_submit_var($_POST["application_id"], 'V', 0, 0, 1, '');
        $application_id_dp3 = check_submit_var($_POST["application_id_dp3"], 'V', 0, 0, 1, '');
        $trade_ref = check_submit_var($_POST["trade_ref"], 'V', 0, 0, 1, '');
        $del_approval = check_submit_var($_POST["del_approval"], 'V', 0, 0, 1, '');
        $signed_application = check_submit_var($_POST["signed_application"], 'V', 0, 0, 1, '');

        $processTaskModel = new ProcessTaskModel($this->db);

        if (!empty($task_id)) {
            //update

            if ($trade_ref != "PENDING" && $del_approval != "PENDING" && $signed_application != "PENDING") {
                $taks_fields[] = array('VPROCESS_TASKS_STATUS', "COMPLETED");
                $taks_fields[] = array('IUPDATEDBY', $_SESSION['user_id']);
                $taks_fields[] = array('DUPDATED_DATE', $cur_datetime);
                $taks_fields[] = array('IPUBLISH', "1");

                $msg = $processTaskModel->updateProcessTask($taks_fields, $task_id);
            }
        }

        if ($trade_ref != "PENDING" && $del_approval != "PENDING" && $signed_application != "PENDING") {

            $appstatusModel = new AppstatusModel($this->db);

            $status_info = $appstatusModel->getStatusByAppID($application_id);

            if (strtoupper(trim($status_info->VDP3STATUS)) == "COMPLETED") {
                /*$data_fields[] = array('VPROCESSING_STATUS', "COMPLETED");*/
            }

            $data_fields[] = array('VPROCESS_TASK_STATUS', "COMPLETED");
            $data_fields[] = array('IUPDATED_BY', $_SESSION['user_id']);
            $data_fields[] = array('DUPDATED_DATETIME', $cur_datetime);

            $msg = $appstatusModel->updateProcessStatus($data_fields, $status_id);

            //add success note to Note table
            $notesModel = new NotesModel($this->db);

            /*$added_date_time = new DateTime($cur_datetime);
            $added_date = date_format($added_date_time, "d-m-Y");
            $added_time = date_format($added_date_time, "g:i:s A");*/

            $added_date = get_country_datetime($time_zone, $cur_datetime, 'd-m-Y');
            $added_time = get_country_datetime($time_zone, $cur_datetime, 'g:i:s A');

            $note_data_fields[] = array('VURL', sha1(rand()));
            $note_data_fields[] = array('IAPPLICATION_ID', $application_id);
            $note_data_fields[] = array('VCATEGORY', "case_manager");
            $note_data_fields[] = array('DDATETIME', $cur_datetime);
            $note_data_fields[] = array('TNOTE_HEADER', "Process Task Complete - Application $application_id_dp3");
            $note_data_fields[] = array('TNOTE', "Processing Task Completed By " . $_SESSION['user_name'] . " on $added_date at $added_time"); // $_SESSION['first_name'] . " " . $_SESSION['last_name']
            $note_data_fields[] = array('IADDED_BY', $_SESSION['user_id']);
            $note_data_fields[] = array('IRECORDID', $status_id); //app_status table id
            $note_data_fields[] = array('VSECTION', "process_tasks");
            $note_data_fields[] = array('IPUBLISH', 1);

            $notesModel->insert($note_data_fields);
        }

        echo $msg;
    }

    /**
     * @prasanna
     * @param type $com_id
     * @param type $com_type
     * @return type
     */
    public function getWSDLCredentials($com_id, $com_type)
    {

        $companyModel = new CompanyModel($this->db);
        $res_com = $companyModel->getCompanyByID($com_id);

        $WEDA_URL = '';
        $WEDA_USERNAME = '';
        $WEDA_PASSWORD = '';

        if ($res_com) {
            if (strtoupper($com_type) == 'COMPANY') {
                $WEDA_URL = $res_com->VCOMPANY_WSDL;
                $WEDA_USERNAME = $res_com->VDP3_USER_NAME;
                $WEDA_PASSWORD = $res_com->VDP3_PASSWORD;
            } else {
                $WEDA_URL = $res_com->VINDIVIDUAL_WSDL;
                $WEDA_USERNAME = $res_com->VDP3_USER_NAME;
                $WEDA_PASSWORD = $res_com->VDP3_PASSWORD;
            }
        }

        $data = array(
            'WEDA_URL' => $WEDA_URL,
            'WEDA_USERNAME' => $WEDA_USERNAME,
            'WEDA_PASSWORD' => $WEDA_PASSWORD
        );

        return $data;
    }

    public function getAPISignature($page_mid, $api_section, $API_KEY, $API_ACCNO, $API_PASSWORD)
    {
        $API_Secret = $API_KEY; //"SC07hUV6C03m5me4GN3hfvvSCsoibY86";
        $API_Account_No = $API_ACCNO; //"12058";
        $API_Password = $API_PASSWORD; //"94b31f7786561e02";

        $pass = $API_Secret . $API_Account_No . $API_Password . $page_mid . $api_section;
        $signature = hash_hmac('sha256', $pass, SIG_KEY);
        $signature = strtoupper($signature);
        return $signature;
    }

    public function passToAPI($url, $data)
    {
        $data_string = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            )
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function sendSignLink($from_Email, $clientemail, $clientname, $message, $Subject, $DP3Code, $ICOMPANY_ID)
    {
        /*
                $MAIL_HOST = 'email-smtp.eu-west-1.amazonaws.com';
                $MAIL_PORT = 587;
                $MAIL_USERNAME = 'AKIAJOL4RE2UMCSI7YQA'; //'
                $MAIL_PASSWORD = 'AgLMGClezBqAScxnc6XRZk+9TalXS+Xq+kPCdQCmZkTN';
        */

        /*$MAIL_HOST = 'mail.sribay.lk';
        $MAIL_PORT = '587';
        $MAIL_USERNAME = 'noreply@sribay.lk'; //'
        $MAIL_PASSWORD = '930803499v';*/

        //Create a new PHPMailer instance
        $mail = new PHPMailer();
        //Tell PHPMailer to use SMTP
        $mail->isSMTP();
        //Enable SMTP debugging
        $mail->SMTPDebug = 0;
        //Ask for HTML-friendly debug output
        $mail->Debugoutput = 'html';
        //Set the hostname of the mail server
        $mail->Host = MAIL_HOST;
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        // I tried PORT 25, 465 too
        $mail->Port = MAIL_PORT;
        //Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = 'tls';
        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = MAIL_USERNAME;
        //Password to use for SMTP authentication
        $mail->Password = MAIL_PASSWORD;
        //Set who the message is to be sent from
        $mail->setFrom($from_Email, 'Credit Application');

        $mail->addAddress($clientemail, $clientname);
        if ($ICOMPANY_ID == 1) {
            $mail->addAttachment('../au/TradeLink/PDF/' . $DP3Code . '.pdf');
        } else if ($ICOMPANY_ID == 2) {
            $mail->addAttachment('../au/laminex/PDF/' . $DP3Code . '.pdf');
        } else if ($ICOMPANY_ID == 3) {
            $mail->addAttachment('../au/fb/PDF/' . $DP3Code . '.pdf');
        } else if ($ICOMPANY_ID == 4) {
            $mail->addAttachment('../nz/fb/PDF/' . $DP3Code . '.pdf');
        } else if ($ICOMPANY_ID == 5) {
            $mail->addAttachment('../au/Rocla/PDF/' . $DP3Code . '.pdf');
        }
        //$mail->addAttachment('./PDF/' . $DP3Code . '.pdf');
        $mail->isHTML(true);  // Set email format to HTML

        $mail->Subject = $Subject;
        $mail->Body = $message;
        // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        if ($mail->send()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param type $application_info
     * @param type $directors_info
     * @param type $guarantors_info
     * @param type $references_info
     * @param type $suppliers_info
     * @param type $caseManagerURL
     */
    public function generatePDF($application_info, $directors_info, $guarantors_info, $references_info, $suppliers_info, $caseManagerURL)
    {

        $mastersettingModel = new MastersettingModel($this->db);
        $supplierModel = new SupplierModel($this->db);
        $companymasterotherModel = new CompanymasterotherModel($this->db);
        $companymasterotherdetailModel = new CompanymasterotherdetailModel($this->db);
        $companyModel = new CompanyModel($this->db);
        $CompanyApiModel = new CompanyApiModel($this->db);

        if (in_array($application_info->ICOMPANY_ID, array(1))) {
            if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                $pdf_html_body = 'pdf_html_body_au_tr_com';
            } else {
                $pdf_html_body = 'pdf_html_body_au_tr_ind';
            }
        } else if (in_array($application_info->ICOMPANY_ID, array(2))) {
            if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                $pdf_html_body = 'pdf_html_body_au_la_com';
            } else {
                $pdf_html_body = 'pdf_html_body_au_la_ind';
            }
        } else if (in_array($application_info->ICOMPANY_ID, array(3))) {
            if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                $pdf_html_body = 'pdf_html_body_au_fb_com';
            } else {
                $pdf_html_body = 'pdf_html_body_au_fb_ind';
            }
        } else {
            if (strtoupper($application_info->VAPPLICATION_TYPE) == 'COMPANY') {
                $pdf_html_body = 'pdf_html_body_nz_com';
            } else {
                $pdf_html_body = 'pdf_html_body_nz_ind';
            }
        }

        $res_companyModel = $companyModel->getCompanyByID($application_info->ICOMPANY_ID);

        $res_com_api = $CompanyApiModel->getCompanyApiByID($application_info->ICOMPANY_ID);

        $file_write_doc_path = substr($res_com_api->VCONTRACT_PATH, 0, -5);

        $where_publish = 1;

        $where_privacy_policy_pdf = 'pdf_privacy_policy';
        $where_terms_condition_pdf = 'pdf_term_and_conditions';

        $sql_statement_policy = $this->db->query_select_to_secure('TBL_COMPANY_POLICY', 'IID, VURL, IPUBLISH, VTITLE, VPDF, VEXTERNAL_URL, VDESCRIPTION, ICOMPANY_ID, VPOLICY_CATEGORY', 'IPUBLISH =? AND ICOMPANY_ID=? AND VPOLICY_CATEGORY=?', 'IID ASC', 0, 0);
        $privacy_policy_pdf = $this->db->query_secure($sql_statement_policy, array($where_publish, $application_info->ICOMPANY_ID, $where_privacy_policy_pdf), TRUE, TRUE, "|");

        $sql_statement_terms_con = $this->db->query_select_to_secure('TBL_COMPANY_POLICY', 'IID, VURL, IPUBLISH, VTITLE, VPDF, VEXTERNAL_URL, VDESCRIPTION, ICOMPANY_ID, VPOLICY_CATEGORY', 'IPUBLISH =? AND ICOMPANY_ID=? AND VPOLICY_CATEGORY=?', 'IID ASC', 0, 0);
        $terms_condition_pdf = $this->db->query_secure($sql_statement_terms_con, array($where_publish, $application_info->ICOMPANY_ID, $where_terms_condition_pdf), TRUE, TRUE, "|");

        $data = array(
            'db' => $this->db,
            'application_info' => $application_info,
            'directors_info' => $directors_info,
            'guarantors_info' => $guarantors_info,
            'references_info' => $references_info,
            'suppliers_info' => $suppliers_info,
            'caseManagerURL' => $caseManagerURL,
            'mastersettingModel' => $mastersettingModel,
            'supplierModel' => $supplierModel,
            'companymasterotherModel' => $companymasterotherModel,
            'companymasterotherdetailModel' => $companymasterotherdetailModel,
            'companyModel' => $companyModel,
            'res_companyModel' => $res_companyModel,
            'privacy_policy_pdf' => $privacy_policy_pdf,
            'terms_condition_pdf' => $terms_condition_pdf,
            'IDP3_APPLICATION_ID' => $application_info->IDP3_APPLICATION_ID,
            'COMPANY_LOGO' => '././uploads/pdf_logo/' . $res_companyModel->VPDF_LOGO,
            'file_write_doc_path' => $file_write_doc_path
        );

        $this->loadView('pdf_html/' . $pdf_html_body, $data);
    }


    function get_date_for_company_tz()
    {

        $company_id = check_submit_var($_POST['company_id'], 'V', 0, 0, 1, '');

        $companyModel = new CompanyModel($this->db);
        $data['company'] = $companyModel->getAllCompanies($company_id);

        $country_code = $data['company'][0]['COUNTRY_CODE'];
        $time_zone = 'Australia/Melbourne';
        if ($country_code == "NZ") {
            $time_zone = 'Pacific/Auckland';
        }
        echo get_country_datetime($time_zone, date("Y-m-d H:i:s"), 'Y-m-d');
    }


    function add_new_director()
    {

        $company_id = check_submit_var($_POST['company_id'], 'V', 0, 0, 1, '');
        $director_no = check_submit_var($_POST['director_no'], 'V', 0, 0, 1, '');
        $application_id = check_submit_var($_POST['application_id'], 'V', 0, 0, 1, '');
        $country_code = check_submit_var($_POST['country_code'], 'V', 0, 0, 1, '');

        $mastersettingModel = new MastersettingModel($this->db);

        $street_type_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'street_type');
        $state_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'state');
        $title_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'title');
        $gender_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'gender');

        $data = array(
            'street_type_list' => $street_type_list,
            'state_list' => $state_list,
            'title_list' => $title_list,
            'gender_list' => $gender_list,
            'db' => $this->db,
            'company_id' => $company_id,
            'director_no' => $director_no,
            'application_id' => $application_id,
            'country_code' => $country_code
        );

        if ($country_code == "AU") {
            $this->loadView('views/manage_application/sub_manage_application/sub_application/director_sub_files/director_add_com_au', $data);
        } else {
            $this->loadView('views/manage_application/sub_manage_application/sub_application/director_sub_files/director_add_com_nz', $data);
        }
    }

    function add_new_individual()
    {

        $company_id = check_submit_var($_POST['company_id'], 'V', 0, 0, 1, '');
        $director_no = check_submit_var($_POST['director_no'], 'V', 0, 0, 1, '');
        $application_id = check_submit_var($_POST['application_id'], 'V', 0, 0, 1, '');
        $country_code = check_submit_var($_POST['country_code'], 'V', 0, 0, 1, '');

        $mastersettingModel = new MastersettingModel($this->db);

        $street_type_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'street_type');
        $state_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'state');
        $title_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'title');
        $gender_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'gender');

        $data = array(
            'street_type_list' => $street_type_list,
            'state_list' => $state_list,
            'title_list' => $title_list,
            'gender_list' => $gender_list,
            'db' => $this->db,
            'company_id' => $company_id,
            'director_no' => $director_no,
            'application_id' => $application_id,
            'country_code' => $country_code
        );

        if ($country_code == "AU") {
            $this->loadView('views/manage_application/sub_manage_application/sub_application/individual_sub_files/individual_add_ind_au', $data);
        } else {
            $this->loadView('views/manage_application/sub_manage_application/sub_application/individual_sub_files/individual_add_ind_nz', $data);
        }
    }

    function add_new_guarantor()
    {

        $company_id = check_submit_var($_POST['company_id'], 'V', 0, 0, 1, '');
        $guarantor_no = check_submit_var($_POST['guarantor_no'], 'V', 0, 0, 1, '');
        $application_id = check_submit_var($_POST['application_id'], 'V', 0, 0, 1, '');
        $country_code = check_submit_var($_POST['country_code'], 'V', 0, 0, 1, '');

        $mastersettingModel = new MastersettingModel($this->db);

        $street_type_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'street_type');
        $state_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'state');
        $title_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'title');
        $gender_list = $mastersettingModel->getMasterSettingByCompanyIdByType($company_id, 'gender');

        $data = array(
            'street_type_list' => $street_type_list,
            'state_list' => $state_list,
            'title_list' => $title_list,
            'gender_list' => $gender_list,
            'db' => $this->db,
            'company_id' => $company_id,
            'guarantor_no' => $guarantor_no,
            'application_id' => $application_id,
            'country_code' => $country_code
        );

        if ($country_code == "AU") {
            $this->loadView('views/manage_application/sub_manage_application/sub_application/guarantor_sub_files/guarantor_add_au', $data);
        } else {
            $this->loadView('views/manage_application/sub_manage_application/sub_application/guarantor_sub_files/guarantor_add_nz', $data);
        }
    }
}

?>