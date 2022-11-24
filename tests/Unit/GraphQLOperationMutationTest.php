<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\Resources\Globals;
use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperationMutation;
use DateTime;
use Tests\TestCase;

class GraphQLOperationMutationTest extends TestCase
{
    public function testMethodResponseObjects()
    {
        $graphQLService = $this->createGraphQLService();
        $graphQLOperationMutation = $this->createGraphQLOperationMutation();
        $graphQLService->setLocale('en');

        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->AdminClientAuthenticate(1, '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientAuthenticate('1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserAuthenticate(1, 'info@example.com', true, '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserAuthenticateByEmail('info@example.com', true, '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserAuthenticateById(1, true, '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserDelete(1, 'info@example.com', '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserUpdate(1, '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserSubscriptionEntitlementManage(1, 1, Globals::ENTITLEMENT_ACTION_FULFIL, new DateTime('now'), '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserProductEntitlementManage(1, 1, Globals::ENTITLEMENT_ACTION_REVOKE, '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserArticleAssetPlay(1, 1, 1, '1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserAuthenticate('info@example.com', '12345678')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserDetailsUpdate()));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserSubscriptionAcquire(1, 1, '/home', 'FOOBAR')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserProductAcquire(1, [1, 2, 3], '/home', 'FOOBAR')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserPaymentVoucherRedeem('FOOBAR', 1, '/home', 1, 1)));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserPaymentAccountOrderValidate(1)));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserDevicePairingClaim('FOOBAR')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->UserDevicePairingDelete(1)));
    }

}
