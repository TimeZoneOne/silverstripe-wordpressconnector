<?php
/**
 * @package silverstripe-wordpressconnector
 */

require_once 'Zend/XmlRpc/Client.php';

/**
 * The base wordpress content source.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressContentSource extends ExternalContentSource {

	public static $db = array(
		'BaseUrl'  => 'Varchar(255)',
		'BlogId'   => 'Int',
		'Username' => 'Varchar(255)',
		'Password' => 'Varchar(255)'
	);

	protected $client;
	protected $valid;
	protected $error;

	/**
	 * @return FieldSet
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		Requirements::css('wordpressconnector/css/WordpressContentSource.css');

		$fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
			'Name', 'BaseUrl', 'BlogId', 'Username', 'Password', 'ShowContentInMenu'
		));

		if ($this->BaseUrl && !$this->isValid()) {
			$error = new LiteralField('ConnError', sprintf(
				'<p id="wordpress-conn-error">%s <span>%s</span></p>',
				$this->fieldLabel('ConnError'), $this->error
			));
			$fields->addFieldToTab('Root.Main', $error, 'Name');
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	public function fieldLabels() {
		return array_merge(parent::fieldLabels(), array(
			'ConnError' => _t('WordpresConnector.CONNERROR', 'Could not connect to the wordpress site:'),
			'BaseUrl'   => _t('WordpressConnector.WPBASEURL', 'Wordpress Base URL'),
			'BlogId'    => _t('WordpressConnector.BLOGID', 'Wordpress Blog ID'),
			'Username'  => _t('WordpressConnector.WPUSER', 'Wordpress Username'),
			'Password'  => _t('WordpressConnector.WPPASS', 'Wordpress Password')
		));
	}

	/**
	 * @return Zend_XmlRpc_Client
	 */
	public function getClient() {
		if (!$this->client) {
			$this->client = new Zend_XmlRpc_Client($this->getApiUrl());
			$this->client->setSkipSystemLookup(true);
		}

		return $this->client;
	}

	public function getContentImporter() {
		return new WordpressImporter();
	}

	/**
	 * @return string
	 */
	public function getApiUrl() {
		return Controller::join_links($this->BaseUrl, 'xmlrpc.php');
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		if (!$this->BaseUrl || !$this->Username || !$this->Password) return;

		if ($this->valid !== null) {
			return $this->valid;
		}

		try {
			$client = $this->getClient();
			$client->call('demo.sayHello');
		} catch (Zend_Exception $ex) {
			$this->error = $ex->getMessage();
			return $this->valid = false;
		}

		return $this->valid = true;
	}

	/**
	 * @return bool
	 */
	public function canImport() {
		return $this->isValid();
	}

}