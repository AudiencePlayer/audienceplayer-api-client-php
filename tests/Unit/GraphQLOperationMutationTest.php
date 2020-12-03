<?php

declare(strict_types=1);

namespace Tests\Unit;

use AudiencePlayer\AudiencePlayerApiClient\Services\GraphQLOperationMutation;
use Tests\TestCase;

class GraphQLOperationMutationTest extends TestCase
{
    public function testMethodResponseObjects()
    {
        $graphQLService = $this->createGraphQLService();
        $graphQLOperationMutation = $this->createGraphQLOperationMutation();
        $graphQLService->setLocale('en');

        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->AdminClientAuthenticate('1', '1', 1)));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientAuthenticate('1', '1')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserAuthenticate('1', '1', 1, 'info@example.com', true)));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserAuthenticateByEmail('1', '1', 'info@example.com', true)));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserAuthenticateById('1', '1', 1, true)));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserDelete('1', '1', 1, 'info@example.com')));
        $this->assertSame(GraphQLOperationMutation::class, get_class($graphQLOperationMutation->ClientUserUpdate('1', '1', 1)));
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
