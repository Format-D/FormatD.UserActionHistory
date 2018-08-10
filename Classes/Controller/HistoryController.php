<?php

namespace FormatD\UserActionHistory\Controller;

use Neos\Flow\Annotations as Flow;
use \Neos\Utility\TypeHandling;

/**
 * Controller for redirecting to history entries
 *
 * @package FormatD\UserActionHistory\Controller
 */
class HistoryController extends \Neos\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \FormatD\UserActionHistory\Domain\Model\UserActionHistory
	 */
	protected $userActionHistory;

	/**
	 * @param string $entryId
	 */
	public function redirectToActionHistoryEntryAction($entryId)
	{
		$this->redirectToRequest($this->userActionHistory->getActionRequestByEntryId($entryId));
	}

}