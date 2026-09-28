<?php
// includes/size-guide-modal.php - Universal Size Guide Modal Popup Component
?>
<div id="sizeGuideModal" class="modal-overlay" onclick="closeSizeGuideModal(event)">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fas fa-ruler-combined text-red-500"></i> SPRINT GEAR SIZE GUIDE</h3>
            <button type="button" class="modal-close-btn" onclick="closeSizeGuideModal(null)">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Size Tabs -->
            <div class="modal-tabs">
                <button type="button" class="tab-btn active" onclick="switchSizeGuideTab('shoes', this)">Shoes & Footwear</button>
                <button type="button" class="tab-btn" onclick="switchSizeGuideTab('tops', this)">T-Shirts & Tops</button>
                <button type="button" class="tab-btn" onclick="switchSizeGuideTab('bottoms', this)">Shorts & Pants</button>
            </div>

            <!-- Shoes Content -->
            <div id="sg-shoes" class="size-guide-content active">
                <h4>Footwear Size Conversion Chart</h4>
                <div class="table-responsive">
                    <table class="size-table">
                        <thead>
                            <tr>
                                <th>EU Size</th>
                                <th>US Men</th>
                                <th>US Women</th>
                                <th>UK Size</th>
                                <th>Foot Length (CM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>38</td><td>6.0</td><td>7.5</td><td>5.5</td><td>24.0 cm</td></tr>
                            <tr><td>39</td><td>6.5</td><td>8.0</td><td>6.0</td><td>24.5 cm</td></tr>
                            <tr><td>40</td><td>7.5</td><td>9.0</td><td>7.0</td><td>25.5 cm</td></tr>
                            <tr><td>41</td><td>8.0</td><td>9.5</td><td>7.5</td><td>26.0 cm</td></tr>
                            <tr><td>42</td><td>8.5</td><td>10.0</td><td>8.0</td><td>26.5 cm</td></tr>
                            <tr><td>43</td><td>9.5</td><td>11.0</td><td>9.0</td><td>27.5 cm</td></tr>
                            <tr><td>44</td><td>10.5</td><td>12.0</td><td>10.0</td><td>28.5 cm</td></tr>
                            <tr><td>45</td><td>11.5</td><td>13.0</td><td>11.0</td><td>29.5 cm</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tops Content -->
            <div id="sg-tops" class="size-guide-content" style="display:none;">
                <h4>Apparel (T-Shirts, Jerseys, Jackets)</h4>
                <div class="table-responsive">
                    <table class="size-table">
                        <thead>
                            <tr>
                                <th>Size Tag</th>
                                <th>Chest (Inches)</th>
                                <th>Length (Inches)</th>
                                <th>Shoulder (Inches)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>XS</td><td>34 - 36"</td><td>27.0"</td><td>16.5"</td></tr>
                            <tr><td>S</td><td>36 - 38"</td><td>28.0"</td><td>17.5"</td></tr>
                            <tr><td>M</td><td>38 - 40"</td><td>29.0"</td><td>18.5"</td></tr>
                            <tr><td>L</td><td>41 - 43"</td><td>30.0"</td><td>19.5"</td></tr>
                            <tr><td>XL</td><td>44 - 46"</td><td>31.0"</td><td>20.5"</td></tr>
                            <tr><td>XXL</td><td>47 - 49"</td><td>32.0"</td><td>21.5"</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottoms Content -->
            <div id="sg-bottoms" class="size-guide-content" style="display:none;">
                <h4>Bottoms (Shorts, Track Pants)</h4>
                <div class="table-responsive">
                    <table class="size-table">
                        <thead>
                            <tr>
                                <th>Size Tag</th>
                                <th>Waist (Inches)</th>
                                <th>Hips (Inches)</th>
                                <th>Inseam (Inches)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>S</td><td>28 - 30"</td><td>35 - 37"</td><td>7.0"</td></tr>
                            <tr><td>M</td><td>31 - 33"</td><td>38 - 40"</td><td>7.5"</td></tr>
                            <tr><td>L</td><td>34 - 36"</td><td>41 - 43"</td><td>8.0"</td></tr>
                            <tr><td>XL</td><td>37 - 39"</td><td>44 - 46"</td><td>8.5"</td></tr>
                            <tr><td>XXL</td><td>40 - 42"</td><td>47 - 49"</td><td>9.0"</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <p class="modal-note"><i class="fas fa-info-circle"></i> Need help choosing your size? Contact our fit specialists at support@sprintgear.lk</p>
        </div>
    </div>
</div>
