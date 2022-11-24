<?php
/**
 * Copyright (c) 2020, AudiencePlayer
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @license     Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 * @author      AudiencePlayer <support@audienceplayer.com>
 * @copyright   AudiencePlayer
 * @link        https://www.audienceplayer.com
 */

declare(strict_types=1);

namespace AudiencePlayer\AudiencePlayerApiClient\Services;

use AudiencePlayer\AudiencePlayerApiClient\Resources\Globals;
use DateTime;

class GraphQLOperationMutation extends GraphQLOperation
{
    /**
     * Authenticate as an OAuth client on admin scope
     *
     * @param int|null $projectId
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function AdminClientAuthenticate(
        int $projectId = null,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_CLIENT,
            Globals::OAUTH_SCOPE_ADMIN,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientAuthenticate',
            [
                'project_id' => $projectId ?: $this->graphQLService->fetchProjectId(),
                'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
                'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            ],
            ['access_token', 'user_id', 'user_email', 'expires_in']
        );
    }

    /**
     * Authenticate as an OAuth client
     *
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientAuthenticate(string $clientId = null, string $clientSecret = null): GraphQLOperationMutation
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_CLIENT,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientAuthenticate',
            [
                'project_id' => $this->graphQLService->fetchProjectId(),
                'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
                'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            ],
            ['access_token', 'user_id', 'user_email', 'expires_in']
        );
    }

    /**
     * As an OAuth client authenticate for given user by userEmail
     *
     * @param string $userEmail
     * @param bool $isAutoRegister
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserAuthenticateByEmail(
        string $userEmail,
        bool $isAutoRegister = false,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        return $this->ClientUserAuthenticate(0, $userEmail, $isAutoRegister, $clientId, $clientSecret);
    }

    /**
     * As an OAuth client authenticate for given user by userId
     *
     * @param int $userId
     * @param bool $isAutoRegister
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserAuthenticateById(
        int $userId,
        bool $isAutoRegister = false,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        return $this->ClientUserAuthenticate($userId, '', $isAutoRegister, $clientId, $clientSecret);
    }

    /**
     * As an OAuth client authenticate for given user
     *
     * @param int $userId
     * @param string $userEmail
     * @param bool $isAutoRegister
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserAuthenticate(
        int $userId,
        string $userEmail,
        bool $isAutoRegister = false,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
        ];

        if ($userId) {
            $args['user_id'] = $userId;
        } elseif ($userEmail) {
            $args['user_email'] = $userEmail;
            $args['auto_register'] = $isAutoRegister;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientUserAuthenticate',
            $args,
            ['access_token', 'user_id', 'user_email', 'expires_in']
        );
    }

    /**
     * As an OAuth client authenticate update a given user
     *
     * @param int $userId
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserUpdate(
        int $userId,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            'id' => $userId,
        ];

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientUserUpdate',
            $args,
            ['id', 'name', 'email']
        );
    }

    /**
     * As an OAuth client authenticate update a given user
     *
     * @param int $userId
     * @param string $userEmail
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserDelete(
        int $userId,
        string $userEmail,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            'id' => $userId,
            'email' => $userEmail,
        ];

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientUserDelete',
            $args,
            []
        );
    }

    /**
     * As an OAuth client manage the subscription entitlement for a given user
     *
     * @param int $userId
     * @param int $subscriptionId
     * @param DateTime|null $expiresAt
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserSubscriptionEntitlementManage(
        int $userId,
        int $subscriptionId,
        string $action,
        DateTime $expiresAt = null,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'action' => [
                'type' => 'enum',
                'value' => Globals::ENTITLEMENT_ACTIONS[$action] ?? null,
            ],
            'expires_at' => $expiresAt ? $expiresAt->format(Globals::DATETIME_ISO8601_FORMAT) : null,
        ];

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientUserSubscriptionEntitlementManage',
            $args,
            []
        );
    }

    /**
     * As an OAuth client manage the product entitlement for a given user
     *
     * @param int $userId
     * @param int $productId
     * @param string $action
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserProductEntitlementManage(
        int $userId,
        int $productId,
        string $action,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            'user_id' => $userId,
            'product_id' => $productId,
            'action' => [
                'type' => 'enum',
                'value' => Globals::ENTITLEMENT_ACTIONS[$action] ?? null,
            ],
        ];

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientUserProductEntitlementManage',
            $args,
            []
        );
    }

    /**
     * As an OAuth client fetch the Article-Asset play config for a given user
     *
     * @param int $userId
     * @param int $articleId
     * @param int $assetId
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientUserArticleAssetPlay(
        int $userId,
        int $articleId,
        int $assetId,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationMutation
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            'user_id' => $userId,
            'article_id' => $articleId,
            'asset_id' => $assetId,
        ];

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientUserArticleAssetPlay',
            $args,
            [
                'appa',
                'appr',
                'duration',
                'aspect_ratio',
                'time_marker_end',
                'time_marker_intro_end',
                'time_marker_intro_start',
                'pulse_token',
                'subtitles {id,url,locale,locale_label}',
                'entitlements {mime_type,manifest,expires_in,token}',
            ]
        );
    }

    public function UserAuthenticate(string $email, string $password): GraphQLOperationMutation
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserAuthenticate',
            [
                'email' => $email,
                'password' => $password,
            ],
            ['access_token', 'user_id', 'user_email', 'expires_in']
        );
    }

    /**
     * Purchase an offered subscription, returns a payment_url to which user can be redirected
     *
     * @return GraphQLOperationMutation
     */
    public function UserDetailsUpdate(): GraphQLOperationMutation
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserDetailsUpdate',
            [],
            ['id', 'name', 'email']
        );
    }

    /**
     * Purchase an offered subscription, returns a payment_url to which user can be redirected
     *
     * @param int $paymentProviderId
     * @param int $subscriptionId
     * @param string $redirectUrlPath
     * @param string $voucherCode
     * @return GraphQLOperationMutation
     */
    public function UserSubscriptionAcquire(
        int $paymentProviderId,
        int $subscriptionId,
        string $redirectUrlPath = '',
        string $voucherCode = ''
    ): GraphQLOperationMutation
    {
        $args = [
            'payment_provider_id' => $paymentProviderId,
            'subscription_id' => $subscriptionId,
        ];

        if ($redirectUrlPath) {
            $args['redirect_url_path'] = $redirectUrlPath;
        }

        if ($voucherCode) {
            $args['voucher_code'] = $voucherCode;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserSubscriptionAcquire',
            $args,
            ['status', 'price', 'title', 'description_short', 'payment_url', 'user_payment_account_order_id']
        );
    }

    /**
     * Purchase an offered product, returns a payment_url to which user can be redirected
     *
     * @param int $paymentProviderId
     * @param array $productIds
     * @param string $redirectUrlPath
     * @param string $voucherCode
     * @return GraphQLOperationMutation
     */
    public function UserProductAcquire(
        int $paymentProviderId,
        array $productIds,
        string $redirectUrlPath = '',
        string $voucherCode = ''
    ): GraphQLOperationMutation
    {
        $arr = [];
        foreach ($productIds as $productId) {
            array_push($arr, '{id:' . $productId . ',purchase_num:1}');
        }

        $args = [
            'payment_provider_id' => $paymentProviderId,
            'product_stack' => [
                'type' => 'object',
                'value' => '[' . implode(',', $arr) . ']',
            ],
        ];


        if ($redirectUrlPath) {
            $args['redirect_url_path'] = $redirectUrlPath;
        }

        if ($voucherCode) {
            $args['voucher_code'] = $voucherCode;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserProductAcquire',
            $args,
            ['status', 'price', 'title', 'description_short', 'payment_url', 'user_payment_account_order_id']
        );
    }

    /**
     * Redeem a (stand-alone) voucher code
     *
     * @param string $voucherCode
     * @param int $paymentProviderId
     * @param string $redirectUrlPath
     * @param int|null $subscriptionId
     * @param int|null $userSubscriptionId
     * @return GraphQLOperationMutation
     */
    public function UserPaymentVoucherRedeem(
        string $voucherCode,
        int $paymentProviderId,
        string $redirectUrlPath = '',
        int $subscriptionId = null,
        int $userSubscriptionId = null
    ): GraphQLOperationMutation
    {
        $args = [
            'code' => $voucherCode,
            'payment_provider_id' => $paymentProviderId,
        ];

        if ($redirectUrlPath) {
            $args['redirect_url_path'] = $redirectUrlPath;
        }

        if ($subscriptionId) {
            $args['subscription_id'] = $subscriptionId;
        }

        if ($userSubscriptionId) {
            $args['user_subscription_id'] = $userSubscriptionId;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserPaymentVoucherRedeem',
            $args,
            ['status', 'price', 'title', 'description_short', 'payment_url', 'user_payment_account_order_id']
        );
    }

    /**
     * Validate a payment order after returning from an external payment provider
     *
     * @param int $userPaymentAccountOrderId
     * @return GraphQLOperationMutation
     */
    public function UserPaymentAccountOrderValidate(int $userPaymentAccountOrderId): GraphQLOperationMutation
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserPaymentAccountOrderValidate',
            ['id' => $userPaymentAccountOrderId],
            []
        );
    }

    /**
     * Complete device pairing by claiming the pairing code displayed in the TV-app
     *
     * @param string $pairingCode
     * @return GraphQLOperationMutation
     */
    public function UserDevicePairingClaim(string $pairingCode): GraphQLOperationMutation
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserDevicePairingClaim',
            ['pairing_code' => ['value' => $pairingCode, 'type' => 'string']],
            ['id', 'name', 'uuid', 'created_at']
        );
    }

    /**
     * Remove a given claimed device from the list
     *
     * @param int $deviceId
     * @return GraphQLOperationMutation
     */
    public function UserDevicePairingDelete(int $deviceId): GraphQLOperationMutation
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'UserDevicePairingDelete',
            ['device_id' => $deviceId],
            []
        );
    }

}
