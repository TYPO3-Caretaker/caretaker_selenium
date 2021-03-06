<?php
/** Copyright 2006 ThoughtWorks, Inc
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * -----------------
 * This file has been automatically generated via XSL
 * -----------------
 *
 *
 *
 * @category   Testing
 * @package    Selenium
 * @author     Shin Ohno <ganchiku at gmail dot com>
 * @author     Bjoern Schotte <schotte at mayflower dot de>
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @version    @package_version@
 * @see        http://www.openqa.org/selenium-rc/
 * @since      0.1
 */

class tx_caretakerselenium_Selenium
{
    /**
     * @var    string
     * @access private
     */
    private $browser;

    /**
     * @var    string
     * @access private
     */
    private $browserUrl;

    /**
     * @var    string
     * @access private
     */
    private $host;

    /**
     * @var    int
     * @access private
     */
    private $port;

    /**
     * @var    string
     * @access private
     */
    private $sessionId;

    /**
     * @var    string
     * @access private
     */
    private $timeout;

    /**
     * Constructor
     *
     * @param string $browser
     * @param string $browserUrl
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @access public
     * @throws tx_caretakerselenium_SeleniumException
     */
    public function __construct($browser, $browserUrl, $host = 'localhost', $port = 4444, $timeout = 30000)
    {
        $this->browser = $browser;
        $this->browserUrl = $browserUrl;
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Run the browser and set session id.
     *
     * @access public
     * @return void
     */
    public function start() {
        $this->sessionId = $this->getString("getNewBrowserSession", array($this->browser, $this->browserUrl));
		if(empty($this->sessionId)) {
			throw new Exception('SessionId is null. Browser seems not to be started.');
		}
        return $this->sessionId;
    }

    /**
     * Close the browser and set session id null
     *
     * @access public
     * @return void
     */
    public function stop()
    {
        $this->doCommand("testComplete");
        $this->sessionId = null;
    }

	public function executeCommand($command) {
		return $this->doCommand($command->command,$command->params);
	}

    protected function doCommand($verb, $args = array()) {
        $url = sprintf('http://%s:%s/selenium-server/driver/?cmd=%s', $this->host, $this->port, urlencode($verb));
        for ($i = 0; $i < count($args); $i++) {
            $argNum = strval($i + 1);
            $url .= sprintf('&%s=%s', $argNum, urlencode(trim($args[$i])));
        }

        if (isset($this->sessionId)) {
            $url .= sprintf('&%s=%s', 'sessionId', $this->sessionId);
        }

        if (!$handle = fopen($url, 'r')) {
            throw new Exception('Cannot connected to Selenium RC Server');
        }

        stream_set_blocking($handle, false);
        $response = stream_get_contents($handle);
        fclose($handle);

        return $response;
    }

	private function getNumber($verb, $args = array())
    {
        $result = $this->getString($verb, $args);

        if (!is_numeric($result)) {
            throw new Exception('result is not numeric.');
        }
        return $result;
    }

    protected function getString($verb, $args = array()) {
        $result = $this->doCommand($verb, $args);
        return (strlen($result) > 3) ? substr($result, 3) : '';
    }

    private function getStringArray($verb, $args = array()) {
        $csv = $this->getString($verb, $args);
        $token = '';
        $tokens = array();
        $letters = preg_split('//', $csv, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0; $i < count($letters); $i++) {
            $letter = $letters[$i];
            switch($letter) {
            case '\\':
                $i++;
                $letter = $letters[$i];
                $token = $token . $letter;
                break;
            case ',':
                array_push($tokens, $token);
                $token = '';
                break;
            default:
                $token = $token . $letter;
                break;
            }
        }
        array_push($tokens, $token);
        return $tokens;
    }

    private function getBoolean($verb, $args = array()) {
        $result = $this->getString($verb, $args);
        switch ($result) {
        case 'true':
            return true;
        case 'false':
            return false;
        default:
            throw new Exception('result is neither "true" or "false": ' . $result);
        }
    }
   

}
?>