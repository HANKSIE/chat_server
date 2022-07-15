<?php
namespace App\Services;

use App\Models\Group;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class MessageService
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function create($userID, $groupID, $body)
    {
        if (!$this->groupService->has($userID, $groupID)) {
            return null;
        }

        return DB::transaction(function () use ($groupID, $userID, $body) {
            $message = Group::find($groupID)->messages()->create(['body' => $body]);
            $message->user()->associate($userID);
            $message->save();
            return $message->fresh()->load(
                $message->group->is_one_to_one ?
                [
                    'user',
                    'group.members',
                ] : 'user'
            );
        });
    }

    public function simplePaginate($groupID, $keyword = '', $perPage = 5)
    {
        $simplePaginate = Message::search($keyword)->where('group_id', $groupID)->orderBy('id', 'desc')->simplePaginate($perPage);
        $simplePaginate->load('user');
        return $simplePaginate;
    }
}
