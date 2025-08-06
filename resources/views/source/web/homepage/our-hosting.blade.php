<!-- CSS cho Modern Pricing Section -->
<style>
        .pricing-section {
            position: relative;
            background: #ffffff;
            padding: 4rem 0;
        }
        
        .pricing-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(0,0,0,0.02) 0%, transparent 70%);
            pointer-events: none;
        }

        .pricing-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
            text-shadow: none;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #666666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .pricing-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 20px;
            padding: 2.5rem;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #000000, #333333, #666666);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: rgba(0,0,0,0.2);
            background: rgba(255,255,255,1);
        }

        .pricing-card:hover::before {
            opacity: 1;
        }

        .pricing-card.featured {
            transform: scale(1.05);
            background: rgba(255,255,255,1);
            border: 2px solid rgba(0,0,0,0.15);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .pricing-card.featured::before {
            opacity: 1;
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            right: 2rem;
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .plan-description {
            color: #666666;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .price-container {
            display: flex;
            align-items: baseline;
            justify-content: center;
            margin-bottom: 0.5rem;
        }

        .price {
            font-size: 3rem;
            font-weight: 800;
            color: #1a1a1a;
            line-height: 1;
        }

        .currency {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333333;
            margin-left: 0.3rem;
        }

        .period {
            color: #888888;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .original-price {
            text-align: center;
            margin-bottom: 1rem;
        }

        .original-price del {
            color: #999999;
            font-size: 1.1rem;
        }

        .features-list {
            list-style: none;
            margin-bottom: 2.5rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            color: #333333;
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .feature-icon {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #000000, #333333);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .feature-icon i {
            font-size: 0.7rem;
            color: #ffffff;
        }

        .cta-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #333333, #000000);
        }

        .btn-secondary {
            background: rgba(0,0,0,0.05);
            color: #333333;
            border: 1px solid rgba(0,0,0,0.2);
        }

        .btn-secondary:hover {
            background: rgba(0,0,0,0.1);
            border-color: rgba(0,0,0,0.3);
            color: #000000;
        }

        .stock-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #856404;
        }

        .comparison-note {
            text-align: center;
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(0,0,0,0.03);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0,0,0,0.1);
        }

        .comparison-note h3 {
            color: #1a1a1a;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .comparison-note p {
            color: #666666;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .pricing-card.featured {
                transform: none;
            }
            
            .section-title {
                font-size: 2.2rem;
            }
            
            .price {
                font-size: 2.5rem;
            }
        }

        /* Animation cho loading */
        .pricing-card {
            animation: slideUp 0.6s ease-out forwards;
        }

        .pricing-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .pricing-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
</style>

<!-- HTML cho Modern Pricing Section -->
<section class="pricing-section">
    <div class="pricing-container">
        <div class="section-header">
            <h2 class="section-title">Hosting Solutions</h2>
            <p class="section-subtitle">
                Choose the perfect hosting plan for your needs. All plans include free SSL, automatic backups, and 24/7 support.
            </p>
        </div>

        <div class="pricing-grid">
            @forelse($featuredProducts as $index => $product)
                <div class="pricing-card {{ $index == 1 ? 'featured' : '' }}">
                    @if($index == 1)
                        <div class="popular-badge">
                            <i class="fas fa-star"></i> Most Popular
                        </div>
                    @endif
                    
                    <div class="pricing-header">
                        <h3 class="plan-name">{{ $product->name }}</h3>
                        <p class="plan-description">
                            {{ $product->short_description ?? 'Perfect for your business needs' }}
                        </p>
                        
                        <div class="price-container">
                            <span class="price">{{ number_format($product->sale_price ?? $product->price, 0, ',', '.') }}</span>
                            <span class="currency">₫</span>
                        </div>
                        <div class="period">
                            @if($product->is_recurring)
                                /{{ $product->recurring_period == 12 ? 'year' : ($product->recurring_period == 1 ? 'month' : $product->recurring_period . ' months') }}
                            @else
                                /one-time
                            @endif
                        </div>
                        
                        @if($product->sale_price && $product->price > $product->sale_price)
                            <div class="original-price">
                                <del>{{ number_format($product->price, 0, ',', '.') }}₫</del>
                            </div>
                        @endif
                    </div>

                    <ul class="features-list">
                        @php
                            // Get features from description or meta_data
                            $features = [];
                            
                            // Check if meta_data has features
                            if ($product->meta_data && isset($product->meta_data['features']) && is_array($product->meta_data['features'])) {
                                $features = $product->meta_data['features'];
                            }
                            
                            // If no features, parse from description
                            if (empty($features) && $product->description) {
                                preg_match_all('/[\-\*]\s*([^\n\r]+)/', strip_tags($product->description), $matches);
                                if (!empty($matches[1])) {
                                    $features = array_slice($matches[1], 0, 6);
                                }
                            }
                            
                            // Default features if none found
                            if (empty($features)) {
                                $features = [
                                    'High Performance SSD Storage',
                                    'Unlimited Bandwidth',
                                    'Free SSL Certificate',
                                    '24/7 Technical Support',
                                    'One-Click App Installation',
                                    'Weekly Automatic Backups'
                                ];
                            }
                        @endphp

                        @foreach(array_slice($features, 0, 6) as $feature)
                            <li class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if($product->stock !== -1 && $product->stock <= 5 && $product->stock > 0)
                        <div class="stock-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Only {{ $product->stock }} items left in stock
                        </div>
                    @endif

                    <div class="cta-buttons">
                        <a href="{{ route('service.detail', $product->slug) }}" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Order Now
                        </a>
                        <a href="{{ route('service.detail', $product->slug) }}" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> View Details
                        </a>
                    </div>
                </div>
            @empty
                <!-- Fallback content nếu không có sản phẩm -->
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3 class="plan-name">Basic Hosting</h3>
                        <p class="plan-description">Perfect for personal websites and small blogs</p>
                        <div class="price-container">
                            <span class="price">199.000</span>
                            <span class="currency">₫</span>
                        </div>
                        <div class="period">/month</div>
                    </div>

                    <ul class="features-list">
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Storage: 5GB SSD</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Bandwidth: Unlimited</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Databases: 3 MySQL</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Free SSL Certificate</span>
                        </li>
                    </ul>

                    <div class="cta-buttons">
                        <a href="{{route('pricing.index')}}" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Order Now
                        </a>
                        <a href="{{route('pricing.index')}}" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> View Details
                        </a>
                    </div>
                </div>

                <div class="pricing-card featured">
                    <div class="popular-badge">
                        <i class="fas fa-star"></i> Most Popular
                    </div>
                    <div class="pricing-header">
                        <h3 class="plan-name">Business Hosting</h3>
                        <p class="plan-description">Best for small and medium businesses</p>
                        <div class="price-container">
                            <span class="price">349.000</span>
                            <span class="currency">₫</span>
                        </div>
                        <div class="period">/month</div>
                    </div>

                    <ul class="features-list">
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Storage: 10GB SSD</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Bandwidth: Unlimited</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Databases: 10 MySQL</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Free SSL Certificate</span>
                        </li>
                    </ul>

                    <div class="cta-buttons">
                        <a href="{{route('pricing.index')}}" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Order Now
                        </a>
                        <a href="{{route('pricing.index')}}" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> View Details
                        </a>
                    </div>
                </div>

                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3 class="plan-name">Premium Hosting</h3>
                        <p class="plan-description">Maximum power for large enterprises</p>
                        <div class="price-container">
                            <span class="price">899.000</span>
                            <span class="currency">₫</span>
                        </div>
                        <div class="period">/month</div>
                    </div>

                    <ul class="features-list">
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Storage: 30GB SSD</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Bandwidth: Unlimited</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Databases: Unlimited</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Free SSL Certificate</span>
                        </li>
                    </ul>

                    <div class="cta-buttons">
                        <a href="{{route('pricing.index')}}" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Order Now
                        </a>
                        <a href="{{route('pricing.index')}}" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> View Details
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="comparison-note">
            <h3><i class="fas fa-shield-alt"></i> Quality Guarantee</h3>
            <p>
                All hosting plans come with 99.9% uptime guarantee, 24/7 technical support, 
                and 30-day money-back policy. Upgrade or downgrade your plan anytime.
            </p>
        </div>
    </div>
</section>

<!-- JavaScript cho Interactive Effects -->
<script>
    // Add smooth scroll and interaction effects
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects for cards
        const cards = document.querySelectorAll('.pricing-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                if (this.classList.contains('featured')) {
                    this.style.transform = 'scale(1.05)';
                } else {
                    this.style.transform = 'translateY(0) scale(1)';
                }
            });
        });

        // Add click effects for buttons
        const buttons = document.querySelectorAll('.btn');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255,255,255,0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    });

    // Add CSS for ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
</script>