<div class="modal fade" tabindex="-1" role="dialog" id="tcg-powertools-import">
    <div class="modal-dialog" role="document">
        <form action="{{ route('article.import.store') }}" enctype="multipart/form-data" method="POST">
            @csrf

            <input type="hidden" name="type" value="tcg-powertools">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Artikelbestand con TCG Power Tools importieren</h5>
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
                        <label for="file">CSV-Datei</label>
                        <input type="file" class="form-control-file" name="file" id="file" required>
                    </div>
                    <div class="card">
                        <div class="card-header">Beispieldatei</div>
                        <div class="card-body">
                            <div>"cardmarketId","quantity","name","set","setCode","cn","condition","language","isFoil","isPlayset","isSigned","price","comment","nameDE","nameES","nameFR","nameIT","rarity"</div>
                            <div>"15073","1","Bridge from Below","Future Sight","FUT","81","NM","English","","","","1000","","Brücke aus der Tiefe","Puente desde lo profundo","Pont des enfers","Ponte con gli Abissi","Rare"</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Importieren</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                </div>
            </div>
        </form>
    </div>
</div>