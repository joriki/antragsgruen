<?php

class PolicyAntraegeLaenderrat extends IPolicyAntraege
{


    /**
     * @static
     * @return int
     */
    static public function getPolicyID()
    {
        return 7;
    }

    /**
     * @static
     * @return string
     */
    static public function getPolicyName()
    {
        return "Länderrat: Gremium oder 3 Mitglieder";
    }


    /**
     * @return bool
     */
    public function checkCurUserHeuristically()
    {
        if ($this->veranstaltung->checkAntragsschlussVorbei()) return false;
        return !Yii::app()->user->isGuest;
    }

    /**
     * @return bool
     */
    public function checkHeuristicallyAssumeLoggedIn() {
        return true;
    }

    /**
     * @abstract
     * @return string
     */
    public function getPermissionDeniedMsg()
    {
        if ($this->veranstaltung->checkAntragsschlussVorbei()) return "Antragsschluss vorbei.";
        if (Yii::app()->user->isGuest) return "Bitte logge dich dafür ein";
        return "";
    }

    /**
     * @return string
     */
    public function getAntragstellerInView()
    {
        return "antragstellerIn_laenderrat";
    }


    /**
     * @return bool
     */
    private function checkSubmit_internal()
    {
        if ($this->veranstaltung->checkAntragsschlussVorbei()) return false;
        if (Yii::app()->user->isGuest) return false;

        if (!isset($_REQUEST["Person"]) || !isset($_REQUEST["Person"]["typ"])) return false;
        if (!$this->isValidName($_REQUEST["Person"]["name"])) return false;

        switch ($_REQUEST["Person"]["typ"]) {
            case "mitglied":
                if (isset($_REQUEST["UnterstuetzerInnen_fulltext"]) && trim($_REQUEST["UnterstuetzerInnen_fulltext"]) != "") return true;

                if (!isset($_REQUEST["UnterstuetzerInnen_name"]) || count($_REQUEST["UnterstuetzerInnen_name"]) < 2) return false;
                $correct = 0;
                foreach ($_REQUEST["UnterstuetzerInnen_name"] as $unters) if ($this->isValidName($unters)) $correct++;
                return ($correct >= 2);
            case "organisation":
                return true;
                break;
            default:
                return false;
        }

    }

    /**
     * @return bool
     */
    public function checkAenderungsantragSubmit()
    {
        return $this->checkSubmit_internal();
    }


    /**
     * @return bool
     */
    public function checkAntragSubmit()
    {
        return $this->checkSubmit_internal();
    }


    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return "Mindestens 3 Länderratsmitglieder oder ein Gremium";
    }
}
