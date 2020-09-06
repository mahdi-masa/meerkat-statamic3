<?php

namespace Stillat\Meerkat\Core\Storage\Data;

use Stillat\Meerkat\Core\Contracts\Comments\CommentContract;
use Stillat\Meerkat\Core\Contracts\Identity\AuthorContract;
use Stillat\Meerkat\Core\Contracts\Identity\AuthorFactoryContract;

/**
 * Class CommentAuthorRetriever
 *
 * Gathers author data from a collection of comments
 *
 * The author retriever is responsible for analyzing a
 * collection of comments to find author information.
 *
 * @package Stillat\Meerkat\Core\Storage\Data
 * @since 2.0.0
 */
class CommentAuthorRetriever
{

    /**
     * An author factory implementation instance.
     *
     * @var AuthorFactoryContract
     */
    protected $authorFactory = null;

    /**
     * A mapping between author email addresses and resolved data.
     *
     * @var array
     */
    protected $authEmailMappings = [];

    public function __construct(AuthorFactoryContract $authorFactory)
    {
        $this->authorFactory = $authorFactory;
    }

    /**
     * Attempts to locate author details from a collection of comments.
     *
     *
     * @param CommentContract[] $comments
     * @return AuthorContract[]
     */
    public function getAuthorDetails($comments)
    {
        if (count($comments) === 0) {
            return [];
        }

        foreach ($comments as $comment) {
            $emailAddress = $comment->getDataAttribute(AuthorContract::KEY_EMAIL_ADDRESS, null);

            if ($emailAddress !== null && mb_strlen(trim($emailAddress)) > 0) {
                if (!array_key_exists($emailAddress, $this->authEmailMappings)) {
                    $this->authEmailMappings[trim($emailAddress)] = $this->getAuthorDataPrototype($comment);
                }
            }
        }

        return array_map(function ($proto) {
            return $this->authorFactory->makeAuthor($proto);
        }, $this->authEmailMappings);
    }

    /**
     * Finds author data prototype data.
     *
     * @param CommentContract $comment The comment to find authorship data in.
     * @return array
     */
    private function getAuthorDataPrototype($comment)
    {
        $emailAddress = $comment->getDataAttribute(AuthorContract::KEY_EMAIL_ADDRESS, null);

        // Guard against empty/missing email address entries.
        if ($emailAddress === null || mb_strlen(trim($emailAddress)) === 0) {
            return null;
        }

        // Locate author details, if available.
        $userIp = $comment->getDataAttribute(AuthorContract::KEY_USER_IP);
        $userAgent = $comment->getDataAttribute(AuthorContract::KEY_USER_AGENT);
        $name = $comment->getDataAttribute(AuthorContract::KEY_NAME);
        $userId = $comment->getDataAttribute(AuthorContract::AUTHENTICATED_USER_ID);
        $webUrl = $comment->getDataAttribute(AuthorContract::KEY_AUTHOR_URL);

        return [
            AuthorContract::KEY_EMAIL_ADDRESS => $emailAddress,
            AuthorContract::KEY_USER_AGENT => $userAgent,
            AuthorContract::KEY_USER_IP => $userIp,
            AuthorContract::KEY_NAME => $name,
            AuthorContract::AUTHENTICATED_USER_ID => $userId,
            AuthorContract::KEY_AUTHOR_URL => $webUrl
        ];
    }

    /***
     * Locates the author details for the provided comment.
     *
     * @param CommentContract $comment The comment to locate the author of.
     * @return AuthorContract|null
     */
    public function getCommentAuthor(CommentContract $comment)
    {
        $emailAddress = $comment->getDataAttribute(AuthorContract::KEY_EMAIL_ADDRESS, null);

        if ($emailAddress !== null && mb_strlen(trim($emailAddress)) > 0) {
            $authorKey = trim($emailAddress);

            if (!array_key_exists($authorKey, $this->authEmailMappings)) {
                $authorPrototype = $this->getAuthorDataPrototype($comment);

                $this->authEmailMappings[$authorKey] = $this->authorFactory->makeAuthor($authorPrototype);
            }

            return $this->authEmailMappings[$authorKey];
        }

        return null;
    }

}
