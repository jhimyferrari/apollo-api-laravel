<div>
    <a href={{ route('index') }}>Voltar para o menu</a>
    <h1>Lista de usuários</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Editar</th>
                <th>Excluir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td><a href={{ route('users.show',$user) }}>🔍</a></td>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><a href={{ route('users.edit',$user) }}>✏️</a></td>
                    <td>   
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
                            @csrf   
                            @method('DELETE')
                            <button type="submit">❌</button>
                        </form></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Nenhum usuário encontrado</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
