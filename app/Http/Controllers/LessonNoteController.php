<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LessonNoteController extends Controller
{
    public function create()
    {
        return view('teacher.notes');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'class' => 'required|string|max:255',
            'topic' => 'required|string|max:255',
            'subTopic' => 'nullable|string|max:255',
            'content' => 'required|array',
        ]);

        JsonDb::init();
        $db = JsonDb::get();
        $user = Session::get('user');

        $note = [
            'id' => 'note_' . uniqid(),
            'teacherId' => $user['id'] ?? 'unknown',
            'subject' => $request->subject,
            'classLevel' => $request->class,
            'topic' => $request->topic,
            'subTopic' => $request->subTopic ?? '',
            'term' => $request->term ?? 'First Term',
            'week' => $request->week ?? 1,
            'difficulty' => $request->difficulty ?? 'Standard',
            'periods' => $request->periods ?? '2 Periods',
        ];

        $note = array_merge($note, $request->content);
        $note['createdAt'] = now()->toIso8601String();

        $db['lessonNotes'][] = $note;
        JsonDb::save($db);

        return redirect()->route('teacher.dashboard')->with('success', 'Lesson note created successfully.');
    }
}
