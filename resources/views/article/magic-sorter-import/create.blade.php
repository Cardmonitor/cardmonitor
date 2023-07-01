<div class="modal fade" tabindex="-1" role="dialog" id="magic-sorter-import">
    <div class="modal-dialog modal-xl" role="document">
        <form action="{{ route('article.import.store') }}" enctype="multipart/form-data" method="POST">
            @csrf

            <input type="hidden" name="type" value="magic-sorter">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Artikel von Magic Sorter importieren</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="game_id">Spiel</label>
                        <select class="form-control" name="game_id" id="game_id">
                            <option value="1">Magic - The Gathering</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="condition">Condition</label>
                        <select class="form-control" name="condition" id="condition">
                            @foreach (\App\Models\Articles\Article::CONDITIONS as $condition => $name)
                                <option value="{{ $condition }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="language_id">language_id</label>
                        <select class="form-control" name="language_id" id="language_id">
                            @foreach (\App\Models\Localizations\Language::GERMAN_TO_IDS as $language_id => $language)
                                <option value="{{ $language_id }}">{{ $language }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="is_foil">Foil</label>
                        <select class="form-control" name="is_foil" id="is_foil">
                            <option value="0">Nein</option>
                            <option value="1">Ja</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="file">CSV-Datei</label>
                        <input type="file" class="form-control-file" name="file" id="file" required>
                    </div>
                    <div class="card">
                        <div class="card-header">Beispieldatei</div>
                        <div class="card-body">
                            <div>set,rarity,title,collector_num,condition,foil,position,height,price,price_trend,ecommerce_id,scryfall_id</div>
                            <div>CMR,rare,"Livio, Oathsworn Sentinel",31,NM,0,11,50,"0,02","0,13",510815,02bce846-8049-4d5c-b0ad-8abd484e5a27</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-primary">Importieren</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Abbrechen</button>
                </div>
            </div>
        </form>
    </div>
</div>