<?php

namespace Shot\Controllers;

/**
 * Album controller
 */
class Album extends \Swiftlet\Controller
{
	/**
	 * Page title
	 * @var string
	 */
	protected $title = 'Album';

	/**
	 * Grid action
	 */
	public function grid()
	{
		$this->view->name = 'album/grid';

		$albumId = $this->app->getArgs(0);

		try {
			$album = $this->app->getModel('album')->load($albumId);
		} catch ( \Swiftlet\Exception $e ) {
			if ( $e->getCode() == $album::EXCEPTION_NOT_FOUND ) {
				$this->app->getLibrary('helpers')->error404();

				return;
			} else {
				throw $e;
			}
		}

		$dbh = $this->app->getLibrary('pdo')->getHandle();

		$sth = $dbh->prepare('
			SELECT
				images.id
			FROM       albums_images
			INNER JOIN images ON albums_images.image_id = images.id
			WHERE
				albums_images.album_id = :album_id
			ORDER BY albums_images.sort_order ASC, images.id ASC
			');

		$sth->bindParam('album_id', $albumId);

		$sth->execute();

		$results = $sth->fetchAll(\PDO::FETCH_OBJ);

		$thumbnails = array();

		foreach ( $results as $result ) {
			$image = $this->app->getModel('image')->load($result->id);

			$paths = array(
				'original' => $image->getFilePath(),
				'preview'  => $image->getFilePath('preview'),
				'thumb'    => $image->getFilePath('thumb')
				);

			foreach ( $image::$imageSizes as $imageSize ) {
				$paths[$imageSize] = $image->getFilePath($imageSize);
			}

			$thumbnails[] = (object) array(
				'id'       => (int) $image->getId(),
				'filename' => $image->getFilename(),
				'title'    => $image->getTitle(),
				'width'    => (int) $image->getWidth(),
				'height'   => (int) $image->getHeight(),
				'path'     => $image->getFilePath('thumb'),
				'paths'    => $paths
				);
		}

		$this->view->pageTitle = $album->getTitle();

		$this->view->thumbnails = $thumbnails;

		$this->view->album = (object) array(
			'title' => $album->getTitle(),
			'id'    => (int) $album->getId()
			);

		$this->view->breadcrumbs = array((object) array(
			'path'  => 'album/grid/' . $album->getId(),
			'title' => $album->getTitle(),
			'icon'  => 'folder'
			));
	}

	/**
	 * Carousel action
	 */
	public function carousel()
	{
		$this->grid();

		$this->view->images = $this->view->thumbnails;

		$this->view->name = 'album/carousel';
	}
}
