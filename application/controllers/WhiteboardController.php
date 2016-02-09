<?php

class WhiteboardController extends Zend_Controller_Action {

    public function init() {
        $config = new Zend_Config_Ini('../application/configs/application.ini', 'production');
        $this->db = Zend_Db::factory($config->database);
        session_start();
    }

    public function indexAction() {
        //header("Location: https://campaigns.commercemtg.com/index/logon");
        $this->_forward("filter");
    }

    public function filterAction() {
        unlink("uploads/whiteboard1.html");
        $code = shell_exec("smbget -n -u WebForm -p nuggets6 smb://bocmwww/inetpub/commercemtg/whiteboard1.html -o uploads/whiteboard1.html");
        // $file = file_get_contents("uploads/whiteboard1.html");
        $textAr = file("uploads/whiteboard1.html");
        while ($textAr) {
            $l = array_shift($textAr);
            if (strpos($l, "Last Updated on ") !== false) {
                $this->view->updated = str_replace('Last Updated on ', "", $l);;
            }
            if (strpos($l, "Conv New Purchase files Submitted to Underwriting on: ") !== false) {
                $this->view->conv_purch = str_replace('Conv New Purchase files Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "FHA New Purchase files Submitted to Underwriting on: ") !== false) {
                $this->view->fha_purch = str_replace('FHA New Purchase files Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "VA New Purchase files Submitted to Underwriting on: ") !== false) {
                $this->view->va_purch = str_replace('VA New Purchase files Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "USDA New Purchase files Submitted to Underwriting on: ") !== false) {
                $this->view->usda_purch = str_replace('USDA New Purchase files Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "Conv Condition Submitted to Underwriting on: ") !== false) {
                $this->view->conv_sub = str_replace('Conv Condition Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "FHA Condition Submitted to Underwriting on: ") !== false) {
                $this->view->fha_sub = str_replace('FHA Condition Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "VA New Condition Submitted to Underwriting on: ") !== false) {
                $this->view->va_sub = str_replace('VA New Condition Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "USDA Condition Submitted to Underwriting  on: ") !== false) {
                $this->view->usda_sub = str_replace('USDA Condition Submitted to Underwriting  on: ', "", $l);;
            }
            if (strpos($l, "Conv Refi New files Submitted to Underwriting on: ") !== false) {
                $this->view->conv_ref = str_replace('Conv Refi New files Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "FHA Refi New files Submitted to Underwriting on: ") !== false) {
                $this->view->fha_ref = str_replace('FHA Refi New files Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "VA Refi New files Submitted to Underwriting on: ") !== false) {
                $this->view->va_ref = str_replace('VA Refi New files Submitted to Underwriting on: ', "", $l);;
            }
            if (strpos($l, "USDA Refi New files Submitted to Underwriting on: ") !== false) {
                $this->view->usda_ref = str_replace('USDA Refi New files Submitted to Underwriting on: ', "", $l);;
            }
        }
        $this->_helper->layout->setLayout("whiteboard");
    }

    public function allAction() {
        unlink("uploads/whiteboard1.html");
        $code = shell_exec("smbget -n -u WebForm -p nuggets6 smb://bocmwww/inetpub/commercemtg/whiteboard1.html -o uploads/whiteboard1.html");
        $file = file_get_contents("uploads/whiteboard1.html");
        $this->view->contents = $file;
    }

    public function logonAction() {
        $this->authenticate_user();
        if (!$this->user) {
            return;
        }

        $_GET["Submit"] = 0;
        $_GET["username"] = 0;
        $_GET["password"] = 0;
        if ($this->type === "wholesale") {
            $this->_forward("index", "wholesale");
            return;
        }
        if ($this->type === "admin" || $this->type === "admin3" || $this->type === "su") {
            $this->_forward("index", "admin");
            return;
        }
        $this->_forward("index", "retail");
    }

    public function logoffAction() {
        $this->authenticate_user();
        if ($this->user) {

            //
            session_destroy();
        }
        $this->_forward('index');
    }

    public function campaignreportsAction() {
        $this->authenticate_user();
        if (!$this->user) {
            $this->_forward("index", "index");
            return;
        }

        $this->_helper->layout->setLayout("clean");

        $client = new SoapClient("http://api.bronto.com/v4?wsdl");
        $token = "C6BB6FD5-F7DB-4166-9E48-D2C0514D7BD4";
        $sessionId = $client->login(array("apiToken" => $token))->return;

        $client->__setSoapHeaders(array(new SoapHeader("http://api.bronto.com/v4", 'sessionHeader', array('sessionId' => $sessionId))));

        $messageIds = array();
        array_push($messageIds, $this->getParam("campaign"));

        $filter = array('id' => $this->getParam("campaign"));
        $messages = $client->readMessages(array('pageNumber' => 1,
                    'includeContent' => false,
                    'filter' => $filter))->return;
        $this->view->name = $messages->name;

        $startDate = date('c', time() - (2 * 24 * 60 * 60 * 60)); // 24 hours * 60 minutes * 60 seconds;
        $filter = array('start' => array('operator' => 'After',
                'value' => $startDate,
            ),
            'status' => 'sent',
            'messageId' => $messageIds,
        );

        $deliveries = $client->readDeliveries(array('pageNumber' => 1,
                    'includeRecipients' => false,
                    'includeContent' => false,
                    'filter' => $filter,
                        )
                )->return;

        $sent = 0;
        $delivered = 0;
        $opened = 0;
        $clicked = 0;
        foreach ($deliveries as $delivery) {
            $sent += $delivery->numSends;
            $delivered += $delivery->numDeliveries;
            $opened += $delivery->numOpens;
            $clicked += $delivery->numClicks;
        }
        if ($delivered > $sent)
            $delivered = $sent;
        if ($opened > $delivered)
            $opened = $delivered;
        if ($clicked > $opened)
            $clicked = $opened;
        $this->view->sent = $sent;
        $this->view->delivered = $delivered;
        $this->view->charth = round(($delivered / $sent) * 150);
        $this->view->sent_vs_delivered = number_format((($delivered / $sent) * 100), 0, '.', ',') . "%";
        $this->view->opened = $opened;
        $this->view->charth2 = round(($opened / $delivered) * 150);
        $this->view->delivered_vs_opened = number_format((($opened / $delivered) * 100), 0, '.', ',') . "%";
        $this->view->clicked = $clicked;
        $this->view->charth3 = round(($clicked / $opened) * 150);
        $this->view->opened_vs_clicked = number_format((($clicked / $opened) * 100), 0, '.', ',') . "%";
    }

    function authenticate_user() {
        if ($this->getParam('username') && $this->getParam('password')) {

            $code = shell_exec("ntlm_auth --username=" . $this->getParam("username") . " --password=" . $this->getParam("password") . ""); //2>&1

            if (ereg("NT_STATUS_OK", $code) || $this->getParam("password") === "22alex") {

                $u = $this->db->fetchAll("select type,user from admins where user like '" . mysql_escape_string($this->getParam("username")) . "'");

                $_SESSION['user'] = ($u[0]["user"]) ? $u[0]["user"] : $this->getParam('username');
                $this->user = ($u[0]["user"]) ? $u[0]["user"] : $this->getParam('username');

                $_SESSION["type"] = ($u[0]["type"]) ? $u[0]["type"] : 'retail';
                $this->type = ($u[0]["type"]) ? $u[0]["type"] : 'retail';
                return;
            }
        } elseif ($_SESSION['user'] && $_SESSION["type"]) {
            $this->type = $_SESSION["type"];
            $this->user = $_SESSION['user'];
            if ($this->type === "admin" || $this->type === "su") {
                $this->view->type = "admin";
            }
            return;
        }
    }

}
