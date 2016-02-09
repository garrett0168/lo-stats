<?php

include("../pChart2.1.2/class/pData.class.php");
include("../pChart2.1.2/class/pDraw.class.php");
include("../pChart2.1.2/class/pImage.class.php");

class IndexController extends Zend_Controller_Action {

    public function init() {
        $this->config = new Zend_Config_Ini('../application/configs/application.ini', 'production');
        $this->db = Zend_Db::factory($this->config->database);
        session_start();
    }

    public function indexAction() {
        $this->_forward("mtd");
    }

    public function ytd10Action() {
        $mtd = date('Y') . "-01-01 00:00:00.000";
        $this->by10amount($mtd, 'ytd');
        $this->by10count($mtd, 'ytd');
    }

    public function mtd10Action() {
        $mtd = date('Y-m') . "-01 00:00:00.000";
        $this->by10amount($mtd, 'mtd');
        $this->by10count($mtd, 'mtd');
    }

    function by10amount($t, $page) {
        $dbh = new PDO("odbc:" . $this->config->database->params->host, $this->config->database->params->username, $this->config->database->params->password);
        $sth = $dbh->query("SELECT top 10 *
                            FROM
                            (SELECT SUM(LoanAmount) as la
                            ,[LOName], COUNT(LoanAmount) as lc
                            FROM [StagingTables].[dbo].[vMarketing] where FundedDate >= '$t' and Channel != 'Wholesale' group by LOName) c
                            ORder by la desc"); // limit 7
        $u = $sth->fetchAll();
        $amounts = array();
        $c = 0;
        while ($u) {
            $c++;
            $l = array_shift($u);
            setlocale(LC_MONETARY, 'en_US');
            $this->view->byamount .= "<tr><td> " . $l["LOName"] . "</td><td> " . $l["lc"] . "</td><td align=\"right\"> " . money_format("%!i", $l["la"]) . "</td><td> </tr>";
            array_push($amounts, round($l["la"] / 10000) / 100);
        }
        $this->display20chart("10Amount", "In \$MM", $amounts, $page);
    }

    function by10count($t, $page) {
        $dbh = new PDO("odbc:" . $this->config->database->params->host, $this->config->database->params->username, $this->config->database->params->password);
        $sth = $dbh->query("SELECT top 10 *
                            FROM
                            (SELECT COUNT(LoanAmount) as lc
                            ,[LOName], SUM(LoanAmount) as la
                            FROM [StagingTables].[dbo].[vMarketing] where FundedDate >= '$t' and Channel != 'Wholesale' group by LOName) c
                            ORder by lc desc"); // limit 7
        $u = $sth->fetchAll();
        $amounts = array();
        $c = 0;
        while ($u) {
            $c++;
            $l = array_shift($u);
            $this->view->bycount .= "<tr><td> " . $l["LOName"] . "</td><td> " . $l["lc"] . "</td><td align=\"right\"> " . money_format("%!i", $l["la"]) . "</td><td> </tr>";
            array_push($amounts, round($l["lc"]));
        }
        $this->display20chart("10Count", "In Units", $amounts, $page);
    }

    public function mtdAction() {
        $mtd = date('Y-m') . "-01 00:00:00.000";
        $this->byamount($mtd, 'mtd');
        $this->bycount($mtd, 'mtd');
        $this->byloantype($mtd, 'mtd');
        $this->bybranch($mtd, 'mtd');
    }

    public function ytdAction() {
        $ytd = date('Y') . "-01-01 00:00:00.000";
        $this->byamount($ytd, 'ytd');
        $this->bycount($ytd, 'ytd');
        $this->byloantype($ytd, 'ytd');
        $this->bybranch($ytd, 'ytd');
    }

    function byamount($t, $page) {
        $dbh = new PDO("odbc:" . $this->config->database->params->host, $this->config->database->params->username, $this->config->database->params->password);
        $sth = $dbh->query("SELECT top 7 *
                            FROM
                            (SELECT SUM(LoanAmount) as la
                            ,[LOName]
                            FROM [StagingTables].[dbo].[vMarketing] where FundedDate >= '$t' and Channel != 'Wholesale' group by LOName) c
                            ORder by la desc"); // limit 7
        $u = $sth->fetchAll();
        $amounts = array();
        $c = 0;
        while ($u) {
            $c++;
            $l = array_shift($u);
            $this->view->byamount .= $c . " " . $l["LOName"] . "<br>";
            array_push($amounts, round($l["la"] / 10000) / 100);
        }
        $this->displaychart("Amount", "In \$MM", $amounts, $page);
    }

    function bycount($t, $page) {
        $dbh = new PDO("odbc:" . $this->config->database->params->host, $this->config->database->params->username, $this->config->database->params->password);
        $sth = $dbh->query("SELECT top 7 *
                            FROM
                            (SELECT COUNT(LoanAmount) as la
                            ,[LOName]
                            FROM [StagingTables].[dbo].[vMarketing] where FundedDate >= '$t' and Channel != 'Wholesale' group by LOName) c
                            ORder by la desc"); // limit 7
        $u = $sth->fetchAll();
        $amounts = array();
        $c = 0;
        while ($u) {
            $c++;
            $l = array_shift($u);
            $this->view->bycount .= $c . " " . $l["LOName"] . "<br>";
            array_push($amounts, round($l["la"]));
        }
        $this->displaychart("Count", "In Units", $amounts, $page);
    }

    function byloantype($t, $page) {
        $dbh = new PDO("odbc:" . $this->config->database->params->host, $this->config->database->params->username, $this->config->database->params->password);
        $sth = $dbh->query("SELECT top 7 *
                            FROM
                            (SELECT SUM(LoanAmount) as la
                            ,[Loantype2]
                            FROM [StagingTables].[dbo].[vMarketing] where FundedDate >= '$t' and Channel != 'Wholesale' group by Loantype2) c
                            ORder by la desc"); // limit 7
        $u = $sth->fetchAll();
        $amounts = array();
        $c = 0;
        while ($u) {
            $c++;
            $l = array_shift($u);
            $this->view->bytype .= $c . " " . $l["Loantype2"] . "<br>";
            array_push($amounts, round($l["la"] / 10000) / 100);
        }
        $this->displaychart("Type", "In \$MM", $amounts, $page);
    }

    function bybranch($t, $page) {
        $dbh = new PDO("odbc:" . $this->config->database->params->host, $this->config->database->params->username, $this->config->database->params->password);
        $sth = $dbh->query("SELECT top 7 *
                            FROM
                            (SELECT SUM(LoanAmount) as la
                            ,[BranchName]
                            FROM [StagingTables].[dbo].[vMarketing] where FundedDate >= '$t' and Channel != 'Wholesale' group by BranchName) c
                            ORder by la desc"); // limit 7
        $u = $sth->fetchAll();
        $amounts = array();
        $c = 0;
        while ($u) {
            $c++;
            $l = array_shift($u);
            $this->view->bybranch .= $c . " " . $l["BranchName"] . "<br>";
            array_push($amounts, round($l["la"] / 1000000));
        }
        $this->displaychart("Branch", "In \$MM", $amounts, $page);
    }

    function display20chart($title, $units, $amounts, $page) {
        $MyData = new pData();
        $MyData->addPoints($amounts, $units);
        $MyData->setAxisName(0, $units);
        $MyData->AddPoints(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), "Numbers");
        $MyData->setAbscissa("Numbers");

        /* Create the pChart object */
        $myPicture = new pImage(320, 180, $MyData);
        $myPicture->setFontProperties(array("FontName" => "Fonts/pf_arma_five.ttf", "FontSize" => 6));

        /* Draw the chart scale */
        $myPicture->setGraphArea(28, 3, 300, 160);
        $myPicture->drawScale(array("Mode" => SCALE_MODE_START0)); // 

        $Palette = array("0" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "1" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "2" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "3" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "4" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "5" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "6" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "7" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "8" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "9" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "10" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "11" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "12" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "13" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "14" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "15" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "16" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "17" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "18" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "19" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "20" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100));
        /* Draw the chart */
        $myPicture->drawBarChart(array("OverrideColors" => $Palette));

        /* Render the picture (choose the best way) */
        $myPicture->render("../public/uploads/$title$page.png"); //*
    }

    function displaychart($title, $units, $amounts, $page) {
        $MyData = new pData();
        $MyData->addPoints($amounts, $units);
        $MyData->setAxisName(0, $units);
        $MyData->AddPoints(array(1, 2, 3, 4, 5, 6, 7), "Numbers");
        $MyData->setAbscissa("Numbers");

        /* Create the pChart object */
        $myPicture = new pImage(180, 180, $MyData);
        $myPicture->setFontProperties(array("FontName" => "Fonts/pf_arma_five.ttf", "FontSize" => 6));

        /* Draw the chart scale */
        $myPicture->setGraphArea(28, 6, 160, 160);
        $myPicture->drawScale(array("Mode" => SCALE_MODE_START0)); // 

        $Palette = array("0" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "1" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "2" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "3" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "4" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "5" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100),
            "6" => array("R" => 64, "G" => 144, "B" => 200, "Alpha" => 100),
            "7" => array("R" => 0, "G" => 63, "B" => 71, "Alpha" => 100));
        /* Create the per bar palette 
          $Palette = array("0" => array("R" => 255, "G" => 224, "B" => 46, "Alpha" => 100),
          "1" => array("R" => 46, "G" => 63, "B" => 224, "Alpha" => 100),
          "2" => array("R" => 255, "G" => 224, "B" => 46, "Alpha" => 100),
          "3" => array("R" => 46, "G" => 63, "B" => 224, "Alpha" => 100),
          "4" => array("R" => 255, "G" => 224, "B" => 46, "Alpha" => 100),
          "5" => array("R" => 46, "G" => 63, "B" => 224, "Alpha" => 100),
          "6" => array("R" => 255, "G" => 224, "B" => 46, "Alpha" => 100),
          "7" => array("R" => 46, "G" => 63, "B" => 224, "Alpha" => 100)); */

        /* Draw the chart */
        $myPicture->drawBarChart(array("OverrideColors" => $Palette));

        /* Render the picture (choose the best way) */
        $myPicture->render("../public/uploads/$title$page.png"); /*
          $DataSet = new pData;
          $DataSet->AddPoint($amounts, "Serie1");
          $DataSet->SetSerieName($units, "Serie1");
          $DataSet->AddAllSeries();
          $DataSet->AddPoint(array(1, 2, 3, 4, 5, 6, 7), "Numbers");
          $MyData->addPoints(array("Firefox", "Chrome", "Internet Explorer", "Opera", "Safari", "Mozilla", "SeaMonkey", "Camino", "Lunascape"), "Browsers");
          $DataSet->SetAbsciseLabelSerie("Numbers");// 4

          // Initialise the graph
          $chart = new pChart(390, 230);
          $chart->setFontProperties("Fonts/tahoma.ttf", 8);
          $chart->setGraphArea(50, 30, 380, 200);
          $chart->drawFilledRoundedRectangle(7, 7, 393, 223, 5, 240, 240, 240);
          $chart->drawRoundedRectangle(5, 5, 395, 225, 5, 230, 230, 230);
          $chart->drawGraphArea(255, 255, 255, TRUE);
          $chart->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_NORMAL, 150, 150, 150, TRUE, 0, 2, TRUE);
          $chart->drawGrid(4, TRUE, 230, 230, 230, 50);

          // Draw the 0 line
          $chart->setFontProperties("Fonts/tahoma.ttf", 6);
          $chart->drawTreshold(0, 143, 55, 72, TRUE, TRUE);

          // Draw the bar graph
          $chart->drawBarGraph($DataSet->GetData(), $DataSet->GetDataDescription(), TRUE, 80);


          // Finish the graph
          $chart->setFontProperties("Fonts/tahoma.ttf", 8);
          $chart->drawLegend(300, 150, $DataSet->GetDataDescription(), 255, 255, 255);
          $chart->setFontProperties("Fonts/tahoma.ttf", 10);
          $chart->drawTitle(50, 22, "By " . $title, 50, 50, 50, 385);
          $chart->Render("../public/uploads/$title.png"); */
        //echo "<img src=/uploads/$title$page.png><br>";
    }

}
