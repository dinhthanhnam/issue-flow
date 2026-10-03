import type { FormEvent } from 'react';
import { useState } from 'react';
import { UserPlus } from 'lucide-react';
import { toast } from 'sonner';
import type { MemberRoleOption } from '@/components/issue-flow/member-management-sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { MemberCandidate } from '@/types/issue-flow';

type Props = {
    roleOptions: MemberRoleOption[];
    onFind: (email: string) => Promise<MemberCandidate>;
    onAdd: (candidate: MemberCandidate, role: string) => Promise<void>;
};

export function AddMemberForm({ roleOptions, onFind, onAdd }: Props) {
    const [email, setEmail] = useState('');
    const [candidate, setCandidate] = useState<MemberCandidate | null>(null);
    const [role, setRole] = useState(roleOptions[0]?.value ?? '');
    const [searching, setSearching] = useState(false);
    const [adding, setAdding] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function findCandidate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (email.trim() === '' || searching) {
            return;
        }

        setSearching(true);
        setCandidate(null);
        setError(null);

        void onFind(email.trim())
            .then(setCandidate)
            .catch(() =>
                setError('No eligible user was found for that exact email.'),
            )
            .finally(() => setSearching(false));
    }

    function addCandidate(): void {
        if (candidate === null || role === '' || adding) {
            return;
        }

        setAdding(true);
        setError(null);

        void onAdd(candidate, role)
            .then(() => {
                toast.success(`${candidate.name} added`);
                setEmail('');
                setCandidate(null);
            })
            .catch(() => setError('Could not add this member.'))
            .finally(() => setAdding(false));
    }

    return (
        <div className="mb-5 rounded-lg border border-dashed p-3">
            <form onSubmit={findCandidate} className="flex gap-2">
                <Input
                    type="email"
                    value={email}
                    onChange={(event) => {
                        setEmail(event.target.value);
                        setCandidate(null);
                        setError(null);
                    }}
                    placeholder="Find user by exact email"
                    aria-label="Candidate email"
                />
                <Button
                    type="submit"
                    variant="outline"
                    disabled={searching || email.trim() === ''}
                >
                    {searching ? 'Finding…' : 'Find'}
                </Button>
            </form>

            {error && <p className="text-destructive mt-2 text-xs">{error}</p>}

            {candidate && (
                <div className="bg-muted/50 mt-3 flex flex-wrap items-center gap-2 rounded-md p-3">
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">
                            {candidate.name}
                        </p>
                        <p className="text-muted-foreground truncate text-xs">
                            {candidate.email}
                        </p>
                    </div>
                    <Select value={role} onValueChange={setRole}>
                        <SelectTrigger
                            size="sm"
                            className="w-28"
                            aria-label="New member role"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {roleOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button
                        type="button"
                        size="sm"
                        disabled={adding}
                        onClick={addCandidate}
                    >
                        <UserPlus className="size-4" />
                        {adding ? 'Adding…' : 'Add'}
                    </Button>
                </div>
            )}
        </div>
    );
}
