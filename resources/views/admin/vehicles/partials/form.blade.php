{{-- Shared Add / Edit vehicle fields. Expects $vehicle (a new, unsaved Vehicle when adding). --}}
        <div class="form-columns">
          <div class="form-column">
            <h3 class="form-column__title">General specifications</h3>
            <div class="form-grid">
              <div class="field field--full">
                <label class="field__label" for="v-title">Vehicle title *</label>
                <input class="field__control" id="v-title" name="title" type="text" required
                       data-validate="text" data-label="Vehicle title" placeholder="e.g. 2022 Toyota Land Cruiser Prado TX-L" value="{{ old('title', $vehicle->title) }}">
                <span class="field__error">@error('title'){{ $message }}@enderror</span>
              </div>

              <div class="field">
                <label class="field__label" for="v-condition">Condition *</label>
                <select class="field__control" id="v-condition" name="condition" required>
                  @foreach ([\App\Enums\VehicleCondition::Tokunbo, \App\Enums\VehicleCondition::BrandNew] as $option)
                    <option value="{{ $option->value }}" @selected(old('condition', $vehicle->condition?->value ?? 'Tokunbo') === $option->value)>{{ $option->value }}</option>
                  @endforeach
                </select>
              </div>

              <div class="field">
                <label class="field__label" for="v-category">Body type *</label>
                <select class="field__control" id="v-category" name="category" required>
                  @foreach (\App\Enums\VehicleCategory::cases() as $option)
                    <option value="{{ $option->value }}" @selected(old('category', $vehicle->category?->value ?? 'SUV') === $option->value)>{{ $option->value }}</option>
                  @endforeach
                </select>
              </div>

              <div class="field">
                <label class="field__label" for="v-make">Make *</label>
                <input class="field__control" id="v-make" name="make" type="text" required
                       data-validate="text" data-label="Make" placeholder="e.g. Toyota" value="{{ old('make', $vehicle->make) }}">
                <span class="field__error">@error('make'){{ $message }}@enderror</span>
              </div>

              <div class="field">
                <label class="field__label" for="v-model">Model *</label>
                <input class="field__control" id="v-model" name="model" type="text" required
                       data-validate="text" data-label="Model" placeholder="e.g. Prado TX-L" value="{{ old('model', $vehicle->model) }}">
                <span class="field__error">@error('model'){{ $message }}@enderror</span>
              </div>

              <div class="field">
                <label class="field__label" for="v-year">Year *</label>
                <input class="field__control" id="v-year" name="year" type="number" min="1990" max="{{ now()->year + 1 }}" required
                       data-validate="text" data-label="Year" placeholder="2022" value="{{ old('year', $vehicle->year) }}">
                <span class="field__error">@error('year'){{ $message }}@enderror</span>
              </div>

              <div class="field">
                <label class="field__label" for="v-mileage">Mileage *</label>
                <input class="field__control" id="v-mileage" name="mileage" type="text" required
                       data-validate="text" data-label="Mileage" placeholder="32,000 km" value="{{ old('mileage', $vehicle->mileage) }}">
                <span class="field__error">@error('mileage'){{ $message }}@enderror</span>
              </div>

              <div class="field">
                <label class="field__label" for="v-transmission">Transmission</label>
                <select class="field__control" id="v-transmission" name="transmission">
                  @foreach (\App\Enums\Transmission::cases() as $option)
                    <option value="{{ $option->value }}" @selected(old('transmission', $vehicle->transmission?->value ?? 'Automatic') === $option->value)>{{ $option->value }}</option>
                  @endforeach
                </select>
              </div>

              <div class="field">
                <label class="field__label" for="v-fuel">Fuel</label>
                <select class="field__control" id="v-fuel" name="fuel">
                  @foreach (\App\Enums\FuelType::cases() as $option)
                    <option value="{{ $option->value }}" @selected(old('fuel', $vehicle->fuel_type?->value ?? 'Petrol') === $option->value)>{{ $option->value }}</option>
                  @endforeach
                </select>
              </div>

              <div class="field">
                <label class="field__label" for="v-status">Status</label>
                <select class="field__control" id="v-status" name="status">
                  @foreach (\App\Enums\VehicleStatus::cases() as $option)
                    <option value="{{ $option->value }}" @selected(old('status', $vehicle->status?->value ?? 'Available') === $option->value)>{{ $option->value }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="form-column">
            <h3 class="form-column__title">Pricing &amp; media</h3>
            <div class="form-grid">
              <div class="field field--full">
                <label class="field__label" for="v-price">Price (NGN) *</label>
                <input class="field__control" id="v-price" name="price" type="number" min="0" step="100000" required
                       data-validate="text" data-label="Price" placeholder="65000000" value="{{ old('price', $vehicle->price) }}">
                <span class="field__error">@error('price'){{ $message }}@enderror</span>
              </div>

              <div class="field field--full">
                <label class="field__label" for="v-image">Vehicle photo</label>
                @if ($vehicle->primary_image)
                  <div class="vehicle-current-photo">
                    <img src="{{ $vehicle->imageUrl() }}" alt="Current photo of {{ $vehicle->title }}" width="96" height="68" loading="lazy">
                    <span>Current photo. Upload a new one below to replace it.</span>
                  </div>
                @endif
                <div class="dropzone" data-dropzone>
                  <input class="dropzone__input" id="v-image" name="image" type="file" accept="image/png,image/jpeg,image/webp" data-dropzone-input>
                  <div class="dropzone__inner" data-dropzone-inner>
                    <svg class="dropzone__icon" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.5 19a4.5 4.5 0 0 0 .5-8.98V9.5a6 6 0 0 0-11.6 1.77A4 4 0 0 0 6.5 19"/><path d="M12 12v6M9.5 15.5 12 13l2.5 2.5"/></svg>
                    <p class="dropzone__title">{{ $vehicle->primary_image ? 'Click to upload a replacement photo or drag and drop' : 'Click to upload car photo or drag and drop' }}</p>
                    <p class="dropzone__hint">PNG, JPG, or WEBP (MAX. 5MB per image)</p>
                  </div>
                  <img class="dropzone__preview" data-dropzone-preview alt="" hidden>
                  <button class="dropzone__remove" type="button" data-dropzone-remove hidden>Remove photo</button>
                </div>
                <span class="field__error">@error('image'){{ $message }}@enderror</span>
              </div>

              <div class="field field--full">
                <label class="field__label" for="v-description">Description *</label>
                <textarea class="field__control" id="v-description" name="description" rows="6" required
                          data-validate="text" data-label="Description"
                          placeholder="Condition notes, service history, inspection findings.">{{ old('description', $vehicle->description) }}</textarea>
                <span class="field__error">@error('description'){{ $message }}@enderror</span>
              </div>

              <div class="field field--full">
                <label class="field__label field__label--check">
                  <input type="checkbox" name="featured" value="1" @checked(old('featured', $vehicle->is_featured))>
                  Show this vehicle in Featured Vehicles on the home page
                </label>
              </div>
            </div>
          </div>
        </div>
