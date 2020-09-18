<?php

namespace Drupal\portland_permalink\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\media\MediaInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * DownloadController class.
 */
class PermalinkController extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * DownloadController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request object.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(RequestStack $request_stack, FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->requestStack = $request_stack;
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('file_system'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Serves the file upon request.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A valid media object.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Serve the file as the response.
   *
   * @throws \Exception
   * @throws NotFoundHttpException
   */
  public function download(MediaInterface $media) {
    $bundle = $media->bundle();
    $source = $media->getSource();
    $config = $source->getConfiguration();
    $field = $config['source_field'];

    // This type has no source field configuration.
    if (!$field) {
      throw new \Exception("No source field configured for the {$bundle} media type.");
    }

    // If a delta was provided, use that.
    $delta = $this->requestStack->getCurrentRequest()->query->get('delta');

    // Get the ID of the requested file by its field delta.
    if (is_numeric($delta)) {
      $values = $media->{$field}->getValue();

      if (isset($values[$delta])) {
        $fid = $values[$delta]['target_id'];
      }
      else {
        throw new NotFoundHttpException("The requested file could not be found.");
      }
    }
    else {
      $fid = $media->{$field}->target_id;
    }

    // If media has no file item.
    if (!$fid) {
      throw new NotFoundHttpException("The media item requested has no file referenced/uploaded in the {$field} field.");
    }

    $file = $this->entityTypeManager()->getStorage('file')->load($fid);

    // Or file entity could not be loaded.
    if (!$file) {
      throw new \Exception("File id {$fid} could not be loaded.");
    }

    $url = $file->url();
    return new RedirectResponse($url);

  //   $uri = $file->getFileUri();
  //   $filename = $file->getFilename();
  //   $scheme = $this->streamWrapperManager->getScheme($uri);

  //   // Or item does not exist on disk.
  //   if (!$this->streamWrapperManager->isValidScheme($scheme) || !file_exists($uri)) {
  //     throw new NotFoundHttpException("The file {$uri} does not exist.");
  //   }

  //   // Let other modules provide headers and controls access to the file.
  //   $headers = $this->moduleHandler()->invokeAll('file_download', [$uri]);

  //   foreach ($headers as $result) {
  //     if ($result == -1) {
  //       throw new AccessDeniedHttpException();
  //     }
  //   }

  //   if (count($headers)) {
  //     // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
  //     // sets response as not cacheable if the Cache-Control header is not
  //     // already modified. We pass in FALSE for non-private schemes for the
  //     // $public parameter to make sure we don't change the headers.
  //     $response = new BinaryFileResponse($uri, Response::HTTP_OK, $headers, $scheme !== 'private');
  //     if (empty($headers['Content-Disposition'])) {
  //       $response->setContentDisposition(
  //         ResponseHeaderBag::DISPOSITION_ATTACHMENT,
  //         $filename
  //       );
  //     }

  //     return $response;
  //   }

  //   throw new AccessDeniedHttpException();
  }

}
