// Components
import { Form, Head, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Email verification" />

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing, wasSuccessful }) => (
                    <>
                        {auth.user.email && (
                            <p className="text-sm text-muted-foreground">
                                We sent a verification link to{' '}
                                <span className="font-medium text-foreground">{auth.user.email}</span>
                            </p>
                        )}

                        {wasSuccessful && (
                            <div className="flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                                <CheckCircle2 className="size-4 shrink-0" />
                                <span>A new verification link has been sent!</span>
                            </div>
                        )}

                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {wasSuccessful ? 'Resend verification email' : 'Send verification email'}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verify email',
    description:
        'Please verify your email address by clicking on the link we just emailed to you.',
};
