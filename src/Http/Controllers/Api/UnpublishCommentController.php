<?php

namespace Stillat\Meerkat\Http\Controllers\Api;

use Exception;
use Statamic\Http\Controllers\CP\CpController;
use Stillat\Meerkat\Core\Comments\Comment;
use Stillat\Meerkat\Core\Contracts\Identity\IdentityManagerContract;
use Stillat\Meerkat\Core\Contracts\Permissions\PermissionsManagerContract;
use Stillat\Meerkat\Core\Contracts\Storage\CommentStorageManagerContract;
use Stillat\Meerkat\Core\Errors;
use Stillat\Meerkat\Core\Exceptions\CommentNotFoundException;
use Stillat\Meerkat\Core\Http\Responses\CommentResponseGenerator;
use Stillat\Meerkat\Core\Http\Responses\Responses;
use Stillat\Meerkat\Core\Logging\ErrorLog;
use Stillat\Meerkat\Core\Logging\ErrorLogContext;
use Stillat\Meerkat\Core\Logging\LocalErrorCodeRepository;
use Stillat\Meerkat\Http\MessageGeneralCommentResponseGenerator;
use Stillat\Meerkat\Http\RequestHelpers;

class UnpublishCommentController extends CpController
{

    public function unPublishMany(
        PermissionsManagerContract $manager,
        IdentityManagerContract $identityManager,
        CommentStorageManagerContract $storageManager)
    {
        $permissions = $manager->getPermissions($identityManager->getIdentityContext());

        if ($permissions->canUnApproveComments === false) {
            if ($this->request->ajax()) {
                return response('Unauthorized.', 401)->header('Meerkat-Permission', Errors::MISSING_PERMISSION_CAN_APPROVE);
            } else {
                abort(403, 'Unauthorized', [
                    'Meerkat-Permission' => Errors::MISSING_PERMISSION_CAN_APPROVE
                ]);
                exit;
            }
        }

        RequestHelpers::setActionFromRequest($this->request);

        $commentIds = $this->request->get(ApiParameters::PARAM_COMMENTS, null);

        if ($commentIds === null || count($commentIds) === 0) {
            return Responses::conditionalWithData(false, [
                ApiParameters::RESULT_COMMENTS => []
            ]);
        }

        try {
            $result = $storageManager->setIsNotApprovedForIds($commentIds);

            return Responses::conditionalWithData($result->success, [
                ApiParameters::RESULT_COMMENTS => $result->comments
            ]);
        } catch (Exception $e) {
            return Responses::generalFailure();
        }
    }

    public function unPublishComment(
        PermissionsManagerContract $manager,
        IdentityManagerContract $identityManager,
        MessageGeneralCommentResponseGenerator $resultGenerator,
        CommentResponseGenerator $commentResultGenerator)
    {
        $permissions = $manager->getPermissions($identityManager->getIdentityContext());

        if ($permissions->canUnApproveComments === false) {
            if ($this->request->ajax()) {
                return response('Unauthorized.', 401)->header('Meerkat-Permission', Errors::MISSING_PERMISSION_CAN_APPROVE);
            } else {
                abort(403, 'Unauthorized', [
                    'Meerkat-Permission' => Errors::MISSING_PERMISSION_CAN_APPROVE
                ]);
                exit;
            }
        }

        RequestHelpers::setActionFromRequest($this->request);

        $commentId = $this->request->get(ApiParameters::PARAM_COMMENT, null);

        if ($commentId === null) {
            return $resultGenerator->notFound('null');
        }

        try {
            $comment = Comment::findOrFail($commentId);
            $result = $comment->unpublish();

            if ($result === true) {
                $comment = Comment::find($commentId);
            }

            return Responses::conditionalWithData($result, [
                ApiParameters::RESULT_COMMENT => $commentResultGenerator->getApiComment($comment->toArray())
            ]);
        } catch (CommentNotFoundException $notFound) {
            return $resultGenerator->notFound($commentId);
        } catch (Exception $e) {
            $context = new ErrorLogContext();
            $context->msg = $e->getMessage();
            $context->details = $e->getTraceAsString();
            LocalErrorCodeRepository::$instance->logError(ErrorLog::make(Errors::GENERAL_EXCEPTION, $context));
            return Responses::generalFailure();
        }
    }

}
