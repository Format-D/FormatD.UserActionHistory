<?php
namespace FormatD\UserActionHistory\Domain\Model;


use Neos\Flow\Annotations as Flow;

/**
 * A history in session of controller actions visited by a user
 *
 * @Flow\Scope("session")
 */
class UserActionHistory {

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	protected $historyEntries;

	/**
	 * @Flow\Inject
	 * @var \Neos\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->historyEntries = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * @param string $description
	 * @param \Neos\Flow\Mvc\ActionRequest $request
	 * @param object $entity
	 * @param array $requestOverride
	 */
	public function addEntry($description, \Neos\Flow\Mvc\ActionRequest $request, $entity = NULL, $requestOverride = NULL)
	{
		$lastEntry = $this->historyEntries->last();

		if ($lastEntry['description'] !== $description || $lastEntry['entity'] !== $entity) {
			if ($requestOverride) {
				if (isset($requestOverride['controller'])) $request->setControllerName($requestOverride['controller']);
				if (isset($requestOverride['action'])) $request->setControllerActionName($requestOverride['action']);
				if (isset($requestOverride['package'])) $request->setControllerPackageKey($requestOverride['package']);
				if (isset($requestOverride['subpackage'])) $request->setControllerSubpackageKey($requestOverride['subpackage']);
				if (isset($requestOverride['arguments'])) $request->setArguments($this->persistenceManager->convertObjectsToIdentityArrays($requestOverride['arguments']));
			}
			$this->historyEntries->add(array('id' => uniqid('ID'), 'description' => $description, 'request' => $request, 'entity' => $entity));
		}
	}

	/**
	 * @param integer $limit
	 * @param string $skipControllerActions
	 * @param boolean $skipDuplicateDescriptions Skip consecutive entries if description is the same
	 */
	public function getLastEntries($limit, $skipControllerActions = '', $skipDuplicateDescriptions = FALSE)
	{
		$entries = array();
		$count = 0;
		$historyEntries = clone $this->historyEntries;
		$lastEntry = NULL;
		while ($entry = $this->getAndRemoveLastEntry($historyEntries, $skipControllerActions)) {
			if ($lastEntry['description'] === $entry['description'] &&
				($skipDuplicateDescriptions || ($lastEntry['entity'] && $entry['entity'] && $lastEntry['entity'] === $entry['entity']))
				) {
				continue;
			}

			$entries[] = $entry;
			$lastEntry = $entry;
			$count++;

			if ($count >= $limit) {
				break;
			}
		}
		return $entries;
	}

	/**
	 * @param string $skipControllerActions
	 * @return \Neos\Flow\Mvc\ActionRequest
	 */
	public function getLastActionRequest($skipControllerActions = '')
	{
		$historyEntries = clone $this->historyEntries;
		$entry = $this->getAndRemoveLastEntry($historyEntries, $skipControllerActions);
		if ($entry) {
			return $entry['request'];
		}
		return NULL;
	}

	/**
	 * @param string $entryId
	 * @return \Neos\Flow\Mvc\ActionRequest
	 */
	public function getActionRequestByEntryId($entryId)
	{
		$criteria = \Doctrine\Common\Collections\Criteria::create()
			->where(\Doctrine\Common\Collections\Criteria::expr()->eq("id", $entryId));

		$entry = $this->historyEntries->matching($criteria)->last();
		if (!$entry) {
			throw new \FormatD\UserActionHistory\Exception('Entry "' . $entryId . '" not found.', 1524142543);
		}
		return $entry['request'];
	}

	/**
	 * Get last entry and remove it
	 *
	 * You can provide controller-actions to exclude with $skipControllerActions
	 * Format: PackageKey:ControllerName->ActionName
	 *
	 * Examples:
	 *	- My.Package:Standard->index
	 *  - User->edit
	 *  - User->edit,MyOtherEntity->edit
	 *
	 * @param \Doctrine\Common\Collections\Collection $historyEntries
	 * @param string $skipControllerActions
	 * @return array
	 */
	protected function getAndRemoveLastEntry($historyEntries, $skipControllerActions = '')
	{
		if ($skipControllerActions !== '') {
			$skipSets = explode(',', $skipControllerActions);

			while ($entry = $historyEntries->last()) {
				$historyEntries->removeElement($entry);

				$skipFlag = FALSE;
				foreach ($skipSets as $skipSet) {
					$parts = explode('->', $skipSet);

					if (count($controllerParts = explode(':', $parts[0])) > 1) {
						$packageName = $controllerParts[0];
						$controllerName = $controllerParts[1];
					} else {
						$packageName = '*';
						$controllerName = $parts[0];
					}

					$actionName = $parts[1];

					if ($packageName === '*' || $packageName === $entry['request']->getControllerPackageKey()) {
						if ($controllerName === '*' || $controllerName === $entry['request']->getControllerName()) {
							if ($actionName === '*' || $actionName === $entry['request']->getControllerActionName()) {
								$skipFlag = TRUE;
								break;
							}
						}
					}
				}
				if (!$skipFlag) {
					return $entry;
				}
			}
		} else {
			$entry = $historyEntries->last();
			$historyEntries->removeElement($entry);
		}

		return $entry;
	}

}