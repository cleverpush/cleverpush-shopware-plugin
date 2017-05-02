<?php

namespace ClvrCleverPush\Models;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity
 * @ORM\Table(name="cleverpush_queued_basket_checks")
 */

class QueuedBasketCheck {
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="basketId", type="string", length=255, nullable=false)
     */
    private $basketId;

    /**
     * @var string
     * @ORM\Column(name="subscriptionId", type="string", length=255, nullable=false)
     */
    private $subscriptionId;

    /**
     * @var DateTime
     * @ORM\Column(name="time", type="datetime", nullable=true)
     */
    private $time;

    public function __construct($basketId, $subscriptionId, $time)
    {
        $this->basketId = $basketId;
        $this->subscriptionId = $subscriptionId;
        $this->time = $time;
    }

    /**
     * @return int
     */

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */

    public function getBasketId() {
        return $this->basketId;
    }

    public function setBasketId($basketId) {
        $this->basketId = $basketId;
    }

    /**
     * @return string
     */
    public function getSubscriptionId() {
        return $this->subscriptionId;
    }

    public function setSubscriptionId($subscriptionId) {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * @return DateTime
     */
    public function getTime() {
        return $this->time;
    }

    public function setTime(DateTime $time) {
        $this->time = $time->format('Y-m-d H:i:s');
    }
}
