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

class GraphQLOperationQuery extends GraphQLOperation
{
    /**
     * As an OAuth client verify a payload
     *
     * @param string $payload
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationQuery
     */
    public function ClientPayloadVerify(
        string $payload,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationQuery
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
            'payload' => $payload,
        ];

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'ClientPayloadVerify',
            $args,
            ['payload', 'status']
        );
    }

    /**
     * @param string $platformContext
     * @param string $operatorContext
     * @return GraphQLOperationQuery
     */
    public function Config(
        string $platformContext = Globals::PLATFORM_CONTEXT_WEB,
        string $operatorContext = Globals::OPERATOR_CONTEXT_WEB
    ): GraphQLOperationQuery
    {
        $args = [
            'platform_context' => [
                'type' => 'enum',
                'value' => Globals::PLATFORM_CONTEXTS[$platformContext] ?? Globals::PLATFORM_CONTEXT_WEB,
            ],
            'operator_context' => [
                'type' => 'enum',
                'value' => Globals::OPERATOR_CONTEXTS[$operatorContext] ?? Globals::OPERATOR_CONTEXT_WEB,
            ],
        ];

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Config',
            $args,
            [
                'project_id',
                'platform {chromecast_receiver_app_id}',
                'language_tags{key,value}',
            ]
        );
    }

    /**
     * As an OAuth client query a user
     *
     * @param int $userId
     * @param string|null $email
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return GraphQLOperationQuery
     */
    public function ClientUser(
        int $userId = 0,
        string $email = null,
        string $clientId = null,
        string $clientSecret = null
    ): GraphQLOperationQuery
    {
        $args = [
            'project_id' => $this->graphQLService->fetchProjectId(),
            'client_id' => $clientId ?: $this->graphQLService->fetchOAuthClientId(),
            'client_secret' => $clientSecret ?: $this->graphQLService->fetchOAuthClientSecret(),
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
    public function UserDetails(): GraphQLOperationQuery
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
    public function UserSubscriptionList(): GraphQLOperationQuery
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
    public function UserProductList(): GraphQLOperationQuery
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
    public function DeviceList(): GraphQLOperationQuery
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
     * @param string $articleUrlSlug
     * @return GraphQLOperationQuery
     */
    public function Article(int $articleId, string $articleUrlSlug = ''): GraphQLOperationQuery
    {
        $args = [];

        if ($articleUrlSlug) {
            $args['url_slug'] = $articleUrlSlug;
        } else {
            $args['id'] = $articleId;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Article',
            $args,
            [
                'id',
                'name',
                'type',
                'metas(output:html)' => 'key,value',
                'categories' => 'id,parent_id,metas(output:html){key,value}',
                'images' => 'url,base_url,base_path,file_name,file_path,aspect_ratio_profile',
                'assets' => 'id,linked_type',
            ]
        );
    }

    /**
     * Fetch list of Articles
     *
     * @param int|null $categoryId
     * @param array $types
     * @return GraphQLOperationQuery
     */
    public function ArticleList(int $categoryId = null, array $types = []): GraphQLOperationQuery
    {
        $args = [];

        if ($categoryId) {
            $args['category_id'] = $categoryId;
        }

        if ($types) {
            $args['types'] = [];
            foreach ($types as $type) {
                if (is_string($type)) {
                    array_push($args['types'], ['type' => 'enum', 'value' => $type]);
                } else {
                    array_push($args['types'], $type);
                }
            }
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
                'categories' => 'id,parent_id,metas(output:html){key,value}',
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
     * @param string $categoryUrlSlug
     * @return GraphQLOperationQuery
     */
    public function Category(int $categoryId, string $categoryUrlSlug = ''): GraphQLOperationQuery
    {
        $args = [];

        if ($categoryUrlSlug) {
            $args['url_slug'] = $categoryUrlSlug;
        } else {
            $args['id'] = $categoryId;
        }

        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Category',
            $args,
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
    public function CategoryList(int $parentId = null): GraphQLOperationQuery
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
     * Fetch details of given subscription
     *
     * @param int $subscriptionId
     * @return GraphQLOperationQuery
     */
    public function Subscription(int $subscriptionId): GraphQLOperationQuery
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Subscription',
            ['id' => $subscriptionId],
            [
                'id',
                'name',
                'type',
                'title',
                'description',
                'description_short',
                'price',
                'currency',
                'currency_symbol',
                'images' => 'url,base_url,base_path,file_name,file_path,aspect_ratio_profile',
            ]
        );
    }

    /**
     * Fetch list of offered subscriptions
     *
     * @param array $paymentProviderIds
     * @return GraphQLOperationQuery
     */
    public function SubscriptionList(array $paymentProviderIds): GraphQLOperationQuery
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
     * Fetch details of given product
     *
     * @param int $productId
     * @return GraphQLOperationQuery
     */
    public function Product(int $productId): GraphQLOperationQuery
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Product',
            ['id' => $productId],
            [
                'id',
                'name',
                'type',
                'title',
                'description',
                'description_short',
                'call_to_action_tag',
                'price',
                'currency',
                'currency_symbol',
                'images' => 'url,base_url,base_path,file_name,file_path,aspect_ratio_profile',
            ]
        );
    }

    /**
     * Fetch list of offered products
     *
     * @param array $paymentProviderIds
     * @return GraphQLOperationQuery
     */
    public function ProductList(array $paymentProviderIds): GraphQLOperationQuery
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

    /**
     * Fetch details of given page
     *
     * @param $pageId
     * @return GraphQLOperationQuery
     */
    public function Page($pageId): GraphQLOperationQuery
    {
        return $this->prepareExecution(
            Globals::OAUTH_ACCESS_AS_AGENT_USER,
            Globals::OAUTH_SCOPE_USER,
            Globals::GRAPHQL_OPERATION_TYPE_QUERY,
            'Page',
            ['id' => $pageId],
            [
                'id',
                'name',
                'full_url_slug',
                'type',
                'title',
                'components {
                    id title content url value
                    elements {
                        id title content url value                    
                        images{url,base_url,base_path,file_name,file_path,aspect_ratio_profile}
                        posters{url,base_url,base_path,file_name,file_path,aspect_ratio_profile}
                    }
                    images{url,base_url,base_path,file_name,file_path,aspect_ratio_profile}
                    posters{url,base_url,base_path,file_name,file_path,aspect_ratio_profile}
                }',
            ]
        );
    }

}
