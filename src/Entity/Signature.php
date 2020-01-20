<?php

namespace App\Entity;

class Signature
{
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $city;
    protected $occupation;
    protected $allow_display = true;
    protected $agree_with_support_statement;
    protected $agree_with_contact_later;

    /**
     * @return mixed
     */
    public function getAgreeWithContactLater()
    {
        return $this->agree_with_contact_later;
    }

    /**
     * @param mixed $agree_with_contact_later
     */
    public function setAgreeWithContactLater($agree_with_contact_later): void
    {
        $this->agree_with_contact_later = $agree_with_contact_later;
    }

    /**
     * @return mixed
     */
    public function getAllowDisplay()
    {
        return $this->allow_display;
    }

    /**
     * @param mixed $allow_display
     */
    public function setAllowDisplay($allow_display): void
    {
        $this->allow_display = $allow_display;
    }

    /**
     * @return mixed
     */
    public function getAgreeWithSupportstatement()
    {
        return $this->agree_with_support_statement;
    }

    /**
     * @param mixed $agree_with_support_statement
     */
    public function setAgreeWithSupportstatement($agree_with_support_statement): void
    {
        $this->agree_with_support_statement = $agree_with_support_statement;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param mixed $first_name
     */
    public function setFirstName($first_name): void
    {
        $this->first_name = $first_name;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param mixed $last_name
     */
    public function setLastName($last_name): void
    {
        $this->last_name = $last_name;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city): void
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getOccupation()
    {
        return $this->occupation;
    }

    /**
     * @param mixed $occupation
     */
    public function setOccupation($occupation): void
    {
        $this->occupation = $occupation;
    }
}