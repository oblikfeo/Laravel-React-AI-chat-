<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Файлы, приложенные к сообщению.
     *
     * Сам файл лежит в хранилище, в базе только путь и описание:
     * складывать содержимое в базу дорого и неудобно для отдачи.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();

            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('name');
            $table->string('mime');
            $table->unsignedBigInteger('size');

            // Извлечённый текст документа. Модель не умеет читать файлы
            // сама: ей отправляется содержимое, а не ссылка на файл.
            $table->longText('extracted_text')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
