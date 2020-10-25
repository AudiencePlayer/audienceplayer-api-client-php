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

class GraphQLOperationQuery extends GraphQLOperation
{
    /**
     * As an OAuth client query a user
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param int $userId
     * @param string|null $email
     * @return GraphQLOperationQuery
     */
    public function ClientUser(string $clientId, string $clientSecret, int $userId = 0, string $email = null)
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];

        if ($userId) {
            $args['id'] = $userId;
        } elseif ($email) {
            $args['email'] = $email;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'ClientUser',
            $args,
            ['id', 'name', 'email']
        );
    }

    /**
     * Fetch user details for current user
     *
     * @return GraphQLOperationQuery
     */
    public function UserDetails()
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'UserDetails',
            [],
            ['id', 'email', 'name']
        );
    }

    /**
     * Fetch list of actual user subscriptions (typically one or none)
     *
     * @return GraphQLOperationQuery
     */
    public function UserSubscriptionList()
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'UserSubscriptionList',
            [],
            [
                'id',
                'subscription_id',
                'suspended_at',
                'suspendable_at',
                'invoiced_at',
                'acquired_at',
                'expires_at',
                'status',
                'status_name',
                'is_valid',
                'is_expired',
                'is_suspendable',
                'is_account_method_changeable',
            ],
            true
        );
    }

    /**
     * Fetch list of actual user subscriptions (typically one or none)
     *
     * @return GraphQLOperationQuery
     */
    public function UserProductList()
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'UserProductList',
            [],
            [
                'id',
                'product_id',
                'is_fulfilled',
                'fulfilment_expires_at',
            ],
            true
        );
    }

    /**
     * Fetch list of all paired devices for current user
     *
     * @return GraphQLOperationQuery
     */
    public function DeviceList()
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'DeviceList',
            [],
            ['id', 'name', 'uuid,', 'created_at'],
            true
        );
    }

    /**
     * Fetch details of given Article
     *
     * @param int $articleId
     * @return GraphQLOperationQuery
     */
    public function Article(int $articleId)
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Article',
            ['id' => $articleId],
            [
                'id',
                'name',
                'type',
                'metas(output:html)' => 'key,value',
                'categories' => 'id,parent_id',
                'images' => 'url,base_url,base_path,file_name,file_path,aspect_ratio_profile',
                'assets' => 'id,linked_type',
            ]
        );
    }

    /**
     * Fetch list of Articles
     *
     * @param int|null $categoryId
     * @return GraphQLOperationQuery
     */
    public function ArticleList(int $categoryId = null)
    {
        $args = [];

        if ($categoryId) {
            $args['category_id'] = $categoryId;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'ArticleList',
            $args,
            [
                'id',
                'name',
                'type',
                'metas(output:html)' => 'key,value',
                'categories' => 'id,parent_id',
                'images' => 'url,base_url,base_path,file_name,file_path,aspect_ratio_profile',
                'assets' => 'id,linked_type',
                'products' => 'id,title,call_to_action_tag,price,currency,currency_symbol,expires_in,expires_at'
            ],
            true
        );
    }


    /**
     * Fetch details of given Category
     *
     * @param int $categoryId
     * @return GraphQLOperationQuery
     */
    public function Category(int $categoryId)
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Category',
            ['id' => $categoryId],
            [
                'id',
                'name',
                'type',
                'metas(output:html)' => 'key,value',
                'images' => 'url,base_url,base_path,file_name,file_path,aspect_ratio_profile',
            ]
        );
    }

    /**
     * Fetch list of Categories
     *
     * @param int|null $parentId
     * @return GraphQLOperationQuery
     */
    public function CategoryList(int $parentId = null)
    {
        $args = [];

        if ($parentId) {
            $args['parent_id'] = $parentId;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'CategoryList',
            $args,
            [
                'id,' .
                'name,' .
                'type,' .
                'metas(output:html){key,value}' .
                'images{url,base_url,base_path,file_name,file_path,aspect_ratio_profile}'
            ],
            true
        );
    }

    /**
     * Fetch list of offered subscriptions
     *
     * @param array $paymentProviderIds
     * @return GraphQLOperationQuery
     */
    public function SubscriptionList(array $paymentProviderIds)
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'SubscriptionList',
            ['payment_provider_ids' => $paymentProviderIds],
            [
                'id',
                'title',
                'description',
                'description_short',
                'price',
                'price_per_installment',
                'time_unit',
                'time_unit_translation',
                'frequency',
                'currency',
                'currency_symbol',
                'images{url,base_url,base_path,file_name,file_path,aspect_ratio_profile}',
            ],
            true
        );
    }

    /**
     * Fetch list of offered products
     *
     * @param array $paymentProviderIds
     * @return GraphQLOperationQuery
     */
    public function ProductList(array $paymentProviderIds)
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'ProductList',
            ['payment_provider_ids' => $paymentProviderIds],
            [
                'id,' .
                'type,' .
                'title,' .
                'description,' .
                'description_short,' .
                'call_to_action_tag,' .
                'price,' .
                'currency,' .
                'currency_symbol,' .
                'expires_in,' .
                'expires_at,' .
                'images{url,base_url,base_path,file_name,file_path,aspect_ratio_profile}'
            ],
            true
        );
    }

}
