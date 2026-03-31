<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageSequence;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageSequenceController extends Controller
{
    public function index()
    {
        $sequences = MessageSequence::with(['template', 'createdBy'])->orderBy('days_after_first_contact')->get();
        return view('admin.message-sequences.index', compact('sequences'));
    }

    public function create()
    {
        $templates = MessageTemplate::where('active', true)->orderBy('name')->get();
        return view('admin.message-sequences.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                     => 'required|string|max:255',
            'days_after_first_contact' => 'required|integer|min:0',
            'template_id'              => 'required|exists:message_templates,id',
            'active'                   => 'boolean',
        ]);

        MessageSequence::create([
            'name'                     => $request->name,
            'days_after_first_contact' => $request->days_after_first_contact,
            'template_id'              => $request->template_id,
            'active'                   => $request->boolean('active', true),
            'created_by'               => $request->user()->id,
        ]);

        return redirect()->route('admin.message-sequences.index')->with('success', 'Message sequence created.');
    }

    public function edit(MessageSequence $messageSequence)
    {
        $templates = MessageTemplate::where('active', true)->orderBy('name')->get();
        return view('admin.message-sequences.edit', compact('messageSequence', 'templates'));
    }

    public function update(Request $request, MessageSequence $messageSequence)
    {
        $request->validate([
            'name'                     => 'required|string|max:255',
            'days_after_first_contact' => 'required|integer|min:0',
            'template_id'              => 'required|exists:message_templates,id',
            'active'                   => 'boolean',
        ]);

        $messageSequence->update([
            'name'                     => $request->name,
            'days_after_first_contact' => $request->days_after_first_contact,
            'template_id'              => $request->template_id,
            'active'                   => $request->boolean('active'),
        ]);

        return redirect()->route('admin.message-sequences.index')->with('success', 'Message sequence updated.');
    }

    public function destroy(MessageSequence $messageSequence)
    {
        $messageSequence->delete();
        return back()->with('success', 'Message sequence deleted.');
    }
}
