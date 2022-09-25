<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageReadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_read', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('message_id')->nullable()->comment('當前已讀的message的id');
            $table->foreignId('latest_message_id')->nullable()->comment('對應的group中id最大的message的id');
            $table->unsignedInteger('unread')->default(0)->comment('未讀數量');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_read');
    }
}
