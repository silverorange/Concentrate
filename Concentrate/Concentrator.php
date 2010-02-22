<?php

require_once 'Concentrate/CacheArray.php';
require_once 'Concentrate/DataProvider.php';

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Concentrator
{
	// {{{ protected properties

	protected $dataProvider = null;

	protected $cache = null;

	// }}}
	// {{{ __construct()

	public function __construct(array $options = array())
	{
		if (array_key_exists('cache', $options)) {
			$this->setCache($options['cache']);
		} else {
			$this->setCache(new Concentrate_CacheArray());
		}

		if (array_key_exists('dataProvider', $options)) {
			$this->setDataProvider($options['dataProvider']);
		} elseif (array_key_exists('data_provider', $options)) {
			$this->setDataProvider($options['data_provider']);
		} else {
			$this->setDataProvider(new Concentrate_DataProvider());
		}
	}

	// }}}
	// {{{ setDataProvider()

	public function setDataProvider(Concentrate_DataProvider $dataProvider)
	{
		$this->dataProvider = $dataProvider;
		return $this;
	}

	// }}}
	// {{{ setCache()

	public function setCache(Concentrate_CacheInterface $cache)
	{
		$this->cache = $cache;
		return $this;
	}

	// }}}
	// {{{ loadDataFile()

	public function loadDataFile($filename)
	{
		$this->dataProvider->loadFile($filename);
		return $this;
	}

	// }}}
	// {{{ loadDataFiles()

	public function loadDataFiles(array $filenames)
	{
		foreach ($filenames as $filename) {
			$this->loadDataFile($filename);
		}
		return $this;
	}

	// }}}
	// {{{ compareFiles()

	public function compareFiles($file1, $file2)
	{
		if ($file1 == $file2) {
			return 0;
		}

		$sortOrder = $this->getFileSortOrder();

		if (!isset($sortOrder[$file1]) && !isset($sortOrder[$file2])) {
			return 0;
		}

		if (isset($sortOrder[$file1]) && !isset($sortOrder[$file2])) {
			return -1;
		}

		if (!isset($sortOrder[$file1]) && isset($sortOrder[$file2])) {
			return 1;
		}

		if ($sortOrder[$file1] < $sortOrder[$file2]) {
			return -1;
		}

		if ($sortOrder[$file1] > $sortOrder[$file2]) {
			return 1;
		}

		return 0;
	}

	// }}}
	// {{{ getConflicts()

	public function getConflicts(array $files)
	{
		$conflicts = array();

		// flip so the files are hash keys to speed lookups
		$files = array_flip($files);

		$fileInfo = $this->getFileInfo();

		foreach ($files as $file => $garbage) {
			if (array_key_exists($file, $fileInfo)) {
				$fileFileInfo = $fileInfo[$file];
				if (   isset($fileFileInfo['Conflicts'])
					&& is_array($fileFileInfo['Conflicts'])
				) {
					foreach ($fileFileInfo['Conflicts'] as $conflict) {
						if (array_key_exists($conflict, $files)) {
							if (!isset($conflicts[$file])) {
								$conflicts[$file] = array();
							}
							$conflicts[$file][] = $conflict;
						}
					}
				}
			}
		}

		return $conflicts;
	}

	// }}}
	// {{{ getCombines()

	public function getCombines(array $files)
	{
		$superset = array();
		$combines = array();

		$combinesInfo = $this->getCombinesInfo();
		foreach ($combinesInfo as $combine => $combinedFiles) {

			$combinedFiles = array_keys($combinedFiles);

			// check if combine does not conflict with existing set and if
			// combine contains one or more files in the required file list
			if (   count(array_intersect($combinedFiles, $superset)) === 0
				&& count(array_intersect($combinedFiles, $files)) > 0
			) {
				$superset   = array_merge($superset, $combinedFiles);
				$combines[] = $combine;
			}

		}

		// add files not included in the combines to the superset
		$superset = array_unique(array_merge($files, $superset));

		// exclude contents of combined sets from file list
		foreach ($combines as $combine) {
			$files = array_diff($files, array_keys($combinesInfo[$combine]));
			$files[] = $combine;
		}

		$info = array(
			// 'combines' contains the combined files that will be included.
			'combines' => $combines,

			// 'superset' contains all original files plus files pulled in by
			// the combined sets. The content of these files will be included.
			'superset' => $superset,

			// 'files' contains combined files and files in the original set
			// that did not fit in any combined set. These are the actual files
			// that will be included.
			'files'    => $files,
		);

		return $info;
	}

	// }}}
	// {{{ getFileSortOrder()

	public function getFileSortOrder()
	{
		$fileSortOrder = $this->getCachedValue('fileSortOrder');
		if ($fileSortOrder === false) {

			$data = $this->dataProvider->getData();

			$fileSortOrder = array();

			// get flat list of file dependencies for each file
			$dependsInfo = $this->getDependsInfo();

			// build into a tree (tree will contain redundant info)
			$tree = array();
			foreach ($dependsInfo as $file => $dependencies) {
				if (!isset($tree[$file])) {
					$tree[$file] = array();
				}
				foreach ($dependencies as $dependentFile) {
					if (!isset($tree[$dependentFile])) {
						$tree[$dependentFile] = array();
					}
					$tree[$file][$dependentFile] =& $tree[$dependentFile];
				}
			}

			// traverse tree to filter out redundant info and get order
			$order = array();
			$this->filterTree($tree, $order);
			$order = array_keys($order);

			$fileSortOrder = array_merge(
				$fileSortOrder,
				$order
			);

			// index by file, with values being the relative sort order
			$fileSortOrder = array_flip($fileSortOrder);

			// add combines as dependencies of all contained files
			$combinesInfo = $this->getCombinesInfo();
			if (count($combinesInfo) > 0) {
				foreach ($combinesInfo as $combine => $files) {

					// get combine dependencies as difference of union of
					// dependencies of contained files and combined set
					$depends = array();
					foreach ($files as $file => $info) {
						if (isset($dependsInfo[$file])) {
							$depends = array_merge(
								$dependsInfo[$file],
								$depends
							);
						}
					}
					$depends = array_diff($depends, array_keys($files));
					$fileSortOrder[$combine] = array();
					foreach ($depends as $depend) {
						$fileSortOrder[$combine][$depend] = array();
					}

					// add combine as dependency of all contained files
					foreach ($files as $file => $info) {
						if (   !isset($fileSortOrder[$file])
							|| !is_array($fileSortOrder[$file])
						) {
							$fileSortOrder[$file] = array();
						}
						$fileSortOrder[$file][$combine] =&
							$fileSortOrder[$combine];
					}
				}

				// re-traverse to get dependency order of combines
				$temp = array();
				$this->filterTree($fileSortOrder, $temp);
				$fileSortOrder = array_keys($temp);

				// index by file, with values being the relative sort order
				$fileSortOrder = array_flip($fileSortOrder);
			}

			$this->cache->set('fileSortOrder', $fileSortOrder);
		}

		return $fileSortOrder;
	}

	// }}}
	// {{{ getFileInfo()

	public function getFileInfo()
	{
		$fileInfo = $this->getCachedValue('fileInfo');
		if ($fileInfo === false) {

			$data = $this->dataProvider->getData();

			$fileInfo = array();

			foreach ($data as $packageId => $info) {
				if (isset($info['Provides']) && is_array($info['Provides'])) {
					foreach ($info['Provides'] as $file => $providesInfo) {
						$providesInfo['Package'] = $packageId;
						$fileInfo[$file] = $providesInfo;
					}
				}
			}

			$this->cache->set('fileInfo', $fileInfo);
		}

		return $fileInfo;
	}

	// }}}
	// {{{ getCombinesInfo()

	public function getCombinesInfo()
	{
		$combinesInfo = $this->getCachedValue('combinesInfo');
		if ($combinesInfo === false) {

			$data = $this->dataProvider->getData();

			$combinesInfo = array();

			foreach ($data as $packageId => $info) {
				if (isset($info['Combines']) && is_array($info['Combines'])) {
					foreach ($info['Combines'] as $combine => $files) {
						// add entries to the set
						foreach ($files as $file) {
							// create entry for the combine set if it does not
							// exist
							if (!isset($combinesInfo[$combine])) {
								$combinesInfo[$combine] = array();
							}
							$combinesInfo[$combine][$file] = array(
								'explicit' => true,
							);
						}
					}
				}
			}

			// Check for dependencies of each set that are not in the set. If
			// a missing dependency also has a dependency on an file in the
			// set, add it to the set.
			$dependsInfo = $this->getDependsInfo();
			foreach ($combinesInfo as $combine => $files) {
				$combinesInfo[$combine] = $this->getImplicitCombinedFiles(
					$files,
					$files
				);
			}

			// sort largest sets first
			uasort($combinesInfo, array($this, 'compareCombines'));

			$this->cache->set('combinesInfo', $combinesInfo);
		}

		return $combinesInfo;
	}

	// }}}
	// {{{ getDependsInfo()

	/**
	 * Gets a flat list of file dependencies for each file
	 *
	 * @return array
	 */
	public function getDependsInfo()
	{
		$dependsInfo = $this->getCachedValue('dependsInfo');
		if ($dependsInfo === false) {

			$data = $this->dataProvider->getData();

			$dependsInfo = array();

			foreach ($this->getPackageSortOrder() as $packageId => $order) {

				if (!isset($data[$packageId])) {
					continue;
				}

				$info = $data[$packageId];

				if (isset($info['Provides']) && is_array($info['Provides'])) {
					foreach ($info['Provides'] as $file => $fileInfo) {
						if (!isset($dependsInfo[$file])) {
							$dependsInfo[$file] = array();
						}
						if (isset($fileInfo['Depends'])) {
							$dependsInfo[$file] = array_merge(
								$dependsInfo[$file],
								$fileInfo['Depends']
							);
						}
						// TODO: some day we could treat optional-depends
						// differently
						if (isset($fileInfo['OptionalDepends'])) {
							$dependsInfo[$file] = array_merge(
								$dependsInfo[$file],
								$fileInfo['OptionalDepends']
							);
						}
					}
				}
			}

			$this->cache->set('dependsInfo', $dependsInfo);
		}

		return $dependsInfo;
	}

	// }}}
	// {{{ getImplicitCombinedFiles()

	protected function getImplicitCombinedFiles(
		array $filesToCheck,
		array $files
	) {
		$dependsInfo = $this->getDependsInfo();

		// get depends
		$depends = array();
		foreach ($filesToCheck as $file => $info) {
			if (isset($dependsInfo[$file])) {
				$depends = array_merge($depends, $dependsInfo[$file]);
			}
		}

		// get depends not in the set
		$depends = array_diff($depends, array_keys($files));

		// check sub-dependencies to see any are in the set
		$implicitFiles = array();
		foreach ($depends as $file) {
			if (isset($dependsInfo[$file])) {
				$subDepends = array_intersect(
					$dependsInfo[$file],
					array_keys($files)
				);
				if (   count($subDepends) > 0
					&& !isset($implicitFiles[$file])
				) {
					$files[$file] = array(
						'explicit' => false,
					);
					$implicitFiles[$file] = $file;
				}
			}
		}

		// if implicit files were added, check those
		if (count($implicitFiles) > 0) {
			$files = $this->getImplicitCombinedFiles(
				$implicitFiles,
				$files
			);
		}

		return $files;
	}

	// }}}
	// {{{ getPackageSortOrder()

	protected function getPackageSortOrder()
	{
		$packageSortOrder = $this->getCachedValue('packageSortOrder');
		if ($packageSortOrder === false) {

			$data = $this->dataProvider->getData();

			// get flat list of package dependencies for each package
			$packageDependencies = array();
			foreach ($data as $packageId => $info) {
				if (!isset($packageDependencies[$packageId])) {
					$packageDependencies[$packageId] = array();
				}
				if (isset($info['Depends'])) {
					$packageDependencies[$packageId] = array_merge(
						$packageDependencies[$packageId],
						$info['Depends']
					);
				}
			}

			// build into a tree (tree will contain redundant info)
			$tree = array();
			foreach ($packageDependencies as $packageId => $dependencies) {
				if (!isset($tree[$packageId])) {
					$tree[$packageId] = array();
				}
				foreach ($dependencies as $dependentPackageId) {
					if (!isset($tree[$dependentPackageId])) {
						$tree[$dependentPackageId] = array();
					}
					$tree[$packageId][$dependentPackageId] =&
						$tree[$dependentPackageId];
				}
			}

			// traverse tree to filter out redundant info and get final order
			$order = array();
			$this->filterTree($tree, $order);
			$order = array_keys($order);

			// return indexed by package id, with values being the relative
			// sort order
			$packageSortOrder = array_flip($order);

			$this->cache->set('packageSortOrder', $packageSortOrder);
		}

		return $packageSortOrder;
	}

	// }}}
	// {{{ compareCombine()

	protected function compareCombines(array $combine1, array $combine2)
	{
		if (count($combine1) < count($combine2)) {
			return 1;
		}

		if (count($combine1) > count($combine2)) {
			return -1;
		}

		return 0;
	}

	// }}}
	// {{{ getCachedValue()

	protected function getCachedValue($key)
	{
		$this->cache->setPrefix($this->dataProvider->getCachePrefix());
		return $this->cache->get($key);
	}

	// }}}
	// {{{ filterTree()

	/**
	 * Performs a depth-first traversal of the given tree and collects an
	 * array of unique values in the traversal order
	 *
	 * @param array $nodes
	 * @param array &$visited
	 *
	 * @return void
	 */
	protected function filterTree(array $nodes, array &$visited)
	{
		foreach ($nodes as $node => $childNodes) {
			if (is_array($childNodes)) {
				$this->filterTree($childNodes, $visited);
			}
			if (!isset($visited[$node])) {
				$visited[$node] = $node;
			}
		}
	}

	// }}}
}

?>
