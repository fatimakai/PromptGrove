<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px">
        <div style="background:#ffffff;border-radius:12px;padding:32px">
            <h1 style="margin-top:0">Welcome to PromptGrove</h1>
            <p>Hi {{ $user->name }},</p>
            <p>Your workspace is ready. You can now create private prompts, publish useful prompts, bookmark community favorites, and keep examples alongside your work.</p>
            <p style="margin-top:28px"><a href="{{ route('prompts.create') }}" style="background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px">Create your first prompt</a></p>
        </div>
    </div>
</body>
</html>
