<?php

namespace Tests\Feature;

use App\Models\ExaminationRoom;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Tests\TestCase;

class ExaminationRoomWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    public function test_room_can_be_updated_using_the_code_rendered_in_its_edit_form(): void
    {
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $response = $this->get(route('admin.examination-rooms.edit', $room))->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $code = (new DOMXPath($document))->query('//input[@name="room_code"]')->item(0)->getAttribute('value');

        $this->put(route('admin.examination-rooms.update', $room), [
            ...$this->data($room), 'room_code' => $code, 'name' => 'Phòng Tai Mũi Họng cập nhật',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.examination-rooms.show', $room));

        $this->assertDatabaseHas('examination_rooms', [
            'id' => $room->id, 'room_code' => 'PKTMH01', 'name' => 'Phòng Tai Mũi Họng cập nhật',
        ]);
    }

    public function test_validation_errors_do_not_replace_the_issued_room_code(): void
    {
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $this->from(route('admin.examination-rooms.edit', $room))
            ->put(route('admin.examination-rooms.update', $room), [...$this->data($room), 'name' => ''])
            ->assertSessionHasErrors('name');

        $this->get(route('admin.examination-rooms.edit', $room))->assertOk()
            ->assertSee('value="PKTMH01"', false)->assertDontSee('Mã dự kiến');
        $this->assertSame('PKTMH01', $room->fresh()->room_code);
    }

    public function test_attempting_to_change_an_issued_code_does_not_update_the_room(): void
    {
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $this->put(route('admin.examination-rooms.update', $room), [
            ...$this->data($room), 'room_code' => 'PK999999', 'name' => 'Không được lưu',
        ])->assertSessionHasErrors('room_code');

        $this->assertSame('PKTMH01', $room->fresh()->room_code);
        $this->assertNotSame('Không được lưu', $room->fresh()->name);
    }

    private function data(ExaminationRoom $room): array
    {
        return $room->only(['room_code', 'name', 'room_number', 'specialty_id', 'status']);
    }
}
