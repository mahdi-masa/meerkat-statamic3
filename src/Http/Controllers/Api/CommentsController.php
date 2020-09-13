<?php

namespace Stillat\Meerkat\Http\Controllers\Api;

use Exception;
use Statamic\Http\Controllers\CP\CpController;
use Stillat\Meerkat\Core\Comments\Comment;
use Stillat\Meerkat\Core\Errors;
use Stillat\Meerkat\Core\Exceptions\CommentNotFoundException;
use Stillat\Meerkat\Core\Exceptions\FilterException;
use Stillat\Meerkat\Core\Http\Responses\CommentResponseGenerator;
use Stillat\Meerkat\Core\Http\Responses\Responses;
use Stillat\Meerkat\Http\MessageGeneralCommentResponseGenerator;
use Stillat\Meerkat\Http\RequestHelpers;

class CommentsController extends CpController
{

    public function search(CommentResponseGenerator $resultGenerator)
    {
        try {
            $resultGenerator->updateFromParameters($this->request->all());

            return Responses::successWithData($resultGenerator->getApiResponse());
        } catch (FilterException $filterException) {
            return Responses::fromErrorCode(Errors::COMMENT_DATA_FILTER_FAILURE, false);
        } catch (Exception $e) {
            return Responses::generalFailure();
        }
    }

    public function publishComment(MessageGeneralCommentResponseGenerator $resultGenerator)
    {
        if ($this->request->ajax()) {
            return response('Unauthorized.', 401)->header('Meerkat-Permission', Errors::MISSING_PERMISSION_CAN_APPROVE);
        } else {
            abort(403, 'Unauthorized', [
                'Meerkat-Permission' => Errors::MISSING_PERMISSION_CAN_APPROVE
            ]);
            exit;
        }

        RequestHelpers::setActionFromRequest($this->request);

        $commentId = $this->request->get('comment', null);

        if ($commentId === null) {
            return $resultGenerator->notFound('null');
        }

        try {
            $comment = Comment::findOrFail($commentId);

            $result = $comment->publish();

            // TODO: SOME CLEAN UP
            return [
                'success' => $result
            ];
        } catch (CommentNotFoundException $notFound) {
            return $resultGenerator->notFound($commentId);
        } catch (Exception $e) {
            return Responses::generalFailure();
        }
        dd($this->request->all());
    }

}
