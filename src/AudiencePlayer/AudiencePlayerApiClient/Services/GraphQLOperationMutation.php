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
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
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

class GraphQLOperationMutation extends GraphQLOperation
{
    /**
     * Authenticate as an OAuth client on admin scope
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param int $projectId
     * @return GraphQLOperationMutation
     */
    public function AdminClientAuthenticate(string $clientId, string $clientSecret, int $projectId)
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_CLIENT,
            Globals::OAUTH_SCOPE_ADMIN,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientAuthenticate',
            [
                'project_id' => $projectId,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
            ['access_token', 'user_id', 'user_email', 'expires_in']
        );
    }

    /**
     * Authenticate as an OAuth client
     *
     * @param string $clientId
     * @param string $clientSecret
     * @return GraphQLOperationMutation
     */
    public function ClientAuthenticate(string $clientId, string $clientSecret)
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_CLIENT,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_MUTATION,
            'ClientAuthenticate',
            [
                'project_id' => $this->graphQLService->fetchProjectId(),
                'client_id' => strval($clientId),
                'client_secret' => strval($clientSecret),
            ],
            ['access_token', 'user_id', 'user_email', 'expires_in']
        );
    }

    /**
     * As an OAuth client authenticate for given user by userEmail
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $userEmail
     * @param bool $isAutoRegister
     * @return GraphQLOperationMutation
     */
    public function ClientUserAuthenticateByEmail(string $clientId, string $clientSecret, string $userEmail, bool $isAutoRegister = false)
    {
        return $this->ClientUserAuthenticate($clientId, $clientSecret, 0, $userEmail, $isAutoRegister);
    }

    /**
     * As an OAuth client authenticate for given user by userId
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param int $userId
     * @param bool $isAutoRegister
     * @return GraphQLOperationMutation
     */
    public function ClientUserAuthenticateById(string $clientId, string $clientSecret, int $userId, bool $isAutoRegister = false)
    {
        return $this->ClientUserAuthenticate($clientId, $clientSecret, $userId, '', $isAutoRegister);
    }

    /**
     * As an OAuth client authenticate for given user
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param int $userId
     * @param string $userEmail
     * @param bool $isAutoRegister
     * @return GraphQLOperationMutation
     */
    public function ClientUserAuthenticate(string $clientId, string $clientSecret, int $userId, string $userEmail, bool $isAutoRegister = false)
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
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
     * @param string $clientId
     * @param string $clientSecret
     * @param int $userId
     * @return GraphQLOperationMutation
     */
    public function ClientUserUpdate(string $clientId, string $clientSecret, int $userId)
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
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
     * @param string $clientId
     * @param string $clientSecret
     * @param int $userId
     * @param string $userEmail
     * @return GraphQLOperationMutation
     */
    public function ClientUserDelete(string $clientId, string $clientSecret, int $userId, string $userEmail)
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
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

    public function UserAuthenticate(string $email, string $password)
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
    public function UserDetailsUpdate()
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
    public function UserSubscriptionAcquire(int $paymentProviderId, int $subscriptionId, string $redirectUrlPath = '', string $voucherCode = '')
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
    public function UserProductAcquire(int $paymentProviderId, array $productIds, string $redirectUrlPath = '', string $voucherCode = '')
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
    )
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
    public function UserPaymentAccountOrderValidate(int $userPaymentAccountOrderId)
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
    public function UserDevicePairingClaim(string $pairingCode)
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
    public function UserDevicePairingDelete(int $deviceId)
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
