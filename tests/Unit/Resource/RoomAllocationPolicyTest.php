<?php

namespace Tests\Unit\Resource;

use App\Modules\Resource\Domain\Services\RoomAllocationPolicy;
use PHPUnit\Framework\TestCase;

class RoomAllocationPolicyTest extends TestCase
{
    public function test_allocates_variable_room_quota_by_demand_weight_and_remainder(): void
    {
        $policy = new RoomAllocationPolicy();

        $quotas = $policy->allocateVariableRoomQuota([
            1 => ['variable_demand' => 2.7, 'total_classes' => 5, 'avg_students' => 20],
            2 => ['variable_demand' => 1.3, 'total_classes' => 3, 'avg_students' => 30],
        ], 4);

        $this->assertSame([1 => 3, 2 => 1], $quotas);
    }

    public function test_distributes_rooms_to_active_departments_when_all_demands_are_zero(): void
    {
        $policy = new RoomAllocationPolicy();

        $quotas = $policy->allocateVariableRoomQuota([
            1 => ['variable_demand' => 0, 'total_classes' => 2, 'avg_students' => 10],
            2 => ['variable_demand' => 0, 'total_classes' => 4, 'avg_students' => 30],
            3 => ['variable_demand' => 0, 'total_classes' => 0, 'avg_students' => 0],
        ], 3);

        $this->assertSame([1 => 1, 2 => 2, 3 => 0], $quotas);
    }
}
