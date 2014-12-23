<?php
namespace O;

class DateTime extends \DateTime implements \JsonSerializable {
  /**
   * An ISO8601 format string for PHP's date functions that's compatible with JavaScript's Date's constructor method
   * Example: 2013-04-12T16:40:00-04:00
   *
   * \DateTime::ISO8601 does not add the colon to the timezone offset which is required for iPhone
   */
  const ISO8601 = 'Y-m-d\TH:i:sP';

  /**
   * Return date in ISO8601 format
   *
   * @return String
   */
  public function __toString() {
    return $this->format(static::ISO8601);
  }

  /**
   * Return date in ISO8601 format
   *
   * @return string
   */
  public function jsonSerialize()
  {
    return (string) $this;
  }

}
