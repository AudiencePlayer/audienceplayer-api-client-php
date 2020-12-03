<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperationQuery;
use Tests\TestCase;

class GraphQLOperationQueryTest extends TestCase
{
    public function testMethodResponseObjects()
    {
        $graphQLOperationQuery = $this->createGraphQLOperationQuery();
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->UserDetails()));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->UserSubscriptionList()));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->UserProductList()));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->DeviceList()));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->ClientPayloadVerify('1', '1', 'foobar')));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->ClientUser('1', '1', 1)));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->ClientUser('1', '1', 0, 'info@exmaple.com')));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->Article(1, '')));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->Article(0, 'foobar')));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->ArticleList(1)));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->Category(1, '')));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->Category(0, 'foobar')));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->CategoryList(1)));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->SubscriptionList([1])));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->Product(1)));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->ProductList([1])));
        $this->assertSame(GraphQLOperationQuery::class, get_class($graphQLOperationQuery->Page(1)));
    }

}
