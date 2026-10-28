<td class="admin-row-number">{{ ($rows instanceof \Illuminate\Contracts\Pagination\Paginator ? ($rows->firstItem() ?? 1) : 1) + $index }}</td>
