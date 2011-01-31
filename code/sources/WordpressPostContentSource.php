<?php
/**
 * An external content source that pulls in wordpress posts.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPostContentSource extends WordpressContentSource {

	public function getRoot() {
		return $this;
	}

	public function getObject($id) {
		$client = $this->getClient($id);
		$id     = $this->decodeId($id);

		$post = $client->call('metaWeblog.getPost', array(
			$id, $this->Username, $this->Password
		));

		if ($post) {
			return WordpressPostContentItem::factory($this, $post);
		}
	}

	public function stageChildren() {
		$result = new DataObjectSet();

		if (!$this->isValid()) {
			return $result;
		}

		// The XML-RPC API has no way to pull all posts by default, so just
		// pass a huge number in as the limit.
		try {
			$client = $this->getClient();
			$posts  = $client->call('metaWeblog.getRecentPosts', array(
				$this->BlogId, $this->Username, $this->Password, 999999
			));
		} catch (Zend_Exception $exception) {
			SS_Log::log($exception, SS_Log::ERR);
			return new DataObjectSet();
		}

		foreach ($posts as $post) {
			$result->push(WordpressPostContentItem::factory($this, $post));
		}

		return $result;
	}

}